<?php

declare(strict_types=1);

namespace Samples\EmbeddedSignerConsumerServices;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use SignNow\Api\Document\Request\DocumentDownloadGet;
use SignNow\Api\Template\Request\CloneTemplatePost;
use Symfony\Component\HttpFoundation\Response;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Response\Data\Role;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\EmbeddedInvite\Request\Data\Invite;
use SignNow\Api\EmbeddedInvite\Request\Data\InviteCollection;
use SignNow\Api\EmbeddedInvite\Request\DocumentInviteLinkPost;
use SignNow\Api\EmbeddedInvite\Request\DocumentInvitePost as DocumentInvitePostRequest;
use SignNow\Api\EmbeddedInvite\Response\DocumentInviteLinkPost as DocumentInviteLinkPostResponse;
use SignNow\Api\EmbeddedInvite\Response\DocumentInvitePost as DocumentInvitePostResponse;
use SignNow\ApiClient;
use SignNow\Sdk;
use SignNow\Api\Template\Response\CloneTemplatePost as CloneTemplatePostResponse;
use SignNow\Api\Document\Response\DocumentDownloadGet as DocumentDownloadGetResponse;

class SampleController implements SampleControllerInterface
{
    private const TEMPLATE_ID = 'b6797f3437db4c818256560e4f68143cb99c7bc9';

    /**
     * Handles GET requests to the sample route.
     *
     * If the `page` parameter is set to 'finish', it returns an HTML page
     * indicating the document was signed and providing a download button.
     * Otherwise, it initiates the signing flow by cloning the template,
     * creating an embedded invite, and redirecting the user to the embedded signing link.
     */
    public function handleGet(Request $request): Response
    {
        $page = $request->get('page');

        if ($page === 'finish') {
            return new Response(
                view('EmbeddedSignerConsumerServices::index')->render(),
                200,
                [
                    'Content-Type' => 'text/html',
                ]
            );
        } else {
            $link = $this->createEmbeddedInviteAndReturnSigningLink(self::TEMPLATE_ID);

            return new RedirectResponse($link);
        }
    }

    /**
     * Handles POST requests to download a completed document.
     *
     * Expects a `document_id` parameter in the request.
     * Uses the SDK to download the document by its ID and returns it as a PDF file response.
     */
    public function handlePost(Request $request): Response
    {
        $documentId = $request->get('document_id');

        $sdk = new Sdk();
        $apiClient = $sdk->build()
            ->authenticate()
            ->getApiClient();

        $file = $this->downloadDocument($apiClient, $documentId);

        return new Response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="completed_document.pdf"',
        ]);
    }

    /**
     * Orchestrates the process of generating an embedded invite signing link.
     *
     * Steps:
     * 1. Authenticates via SDK and clones the document from a template.
     * 2. Retrieves the signer's role ID from the cloned document.
     * 3. Creates an embedded invite for the signer.
     * 4. Generates and returns the embedded signing link with a redirect back to the finish page.
     */
    private function createEmbeddedInviteAndReturnSigningLink(
        string $templateId
    ): string {
        $sdk = new Sdk();
        $apiClient = $sdk->build()
            ->authenticate()
            ->getApiClient();

        $cloneTemplateResponse = $this->createDocumentFromTemplate(
            $apiClient,
            $templateId,
        );

        $signerEmail = config('signnow.api.signer_email');

        $roleId = $this->getSignerUniqueRoleId($apiClient, $cloneTemplateResponse->getId(), "Recipient 1");

        $documentInviteResponse = $this->createEmbeddedInviteForOneSigner(
            $apiClient,
            $cloneTemplateResponse->getId(),
            $signerEmail,
            $roleId
        );

        return $this->getEmbeddedInviteLink(
            $apiClient,
            $cloneTemplateResponse->getId(),
            $documentInviteResponse->getData()->first()->getId()
        );
    }

    /**
     * Sends a request to the SignNow API to create a document from a template.
     *
     * Returns a `CloneTemplatePostResponse` containing the new document ID.
     */
    private function createDocumentFromTemplate(
        ApiClient $apiClient,
        string $templateId,
    ): CloneTemplatePostResponse {
        $cloneTemplate = new CloneTemplatePost();
        $cloneTemplate->withTemplateId($templateId);

        /**@var CloneTemplatePostResponse $cloneTemplateResponse*/
        $cloneTemplateResponse = $apiClient->send($cloneTemplate);

        return $cloneTemplateResponse;
    }

    /**
     * Generates a URL link for the embedded signing session.
     *
     * Takes the document ID and invite ID, then builds a redirect URL
     * to the 'finish' page, which will be used after signing is completed.
     */
    private function getEmbeddedInviteLink(
        ApiClient $apiClient,
        string $documentId,
        string $inviteId,
    ): string {
        $embeddedInvite = new DocumentInviteLinkPost("none", 15);
        $embeddedInvite->withFieldInviteId($inviteId);
        $embeddedInvite->withDocumentId($documentId);

        /**@var DocumentInviteLinkPostResponse $embeddedInviteResponse*/
        $embeddedInviteResponse = $apiClient->send($embeddedInvite);

        $redirectUrl = config('app.url')
            . '/samples/EmbeddedSignerConsumerServices?page=finish&document_id='
            . $documentId;

        return $embeddedInviteResponse->getData()->getLink() . '&redirect_uri=' . urlencode($redirectUrl);
    }

    /**
     * Sends a request to create an embedded invite for a single signer.
     *
     * Accepts the document ID, signer email, and role ID.
     * Returns a `DocumentInvitePostResponse` containing invite data.
     */
    private function createEmbeddedInviteForOneSigner(
        ApiClient $apiClient,
        string $documentId,
        string $signerEmail,
        string $roleId,
    ): DocumentInvitePostResponse {
        $documentInvite = new DocumentInvitePostRequest(
            invites: new InviteCollection(
                [
                    new Invite(
                        email: $signerEmail,
                        roleId: $roleId,
                        order: 1,
                        authMethod: 'none',
                    ),
                ]
            ),
        );

        /**@var DocumentInvitePostResponse $documentInviteResponse*/
        $documentInviteResponse = $apiClient->send($documentInvite->withDocumentId($documentId));

        return $documentInviteResponse;
    }

    /**
     * Retrieves the unique role ID for the signer from the cloned document.
     *
     * It looks for a role by name (e.g., "Recipient 1") and returns its unique ID,
     * which is required to assign the signer to the correct role in the invite.
     */
    private function getSignerUniqueRoleId(
        ApiClient $apiClient,
        string $documentId,
        string $signerRole
    ): string {
        /** @var DocumentGetResponse $document */
        $document = $apiClient->send(
            (new DocumentGet())->withDocumentId($documentId)
        );

        $roleUniqueId = null;
        foreach ($document->getRoles() as $role) {
            /**@var Role $role*/
            if ($role->getName() === $signerRole) {
                $roleUniqueId = $role->getUniqueId();
                break;
            }
        }

        return $roleUniqueId;
    }

    /**
     * Downloads the finalized signed document as a PDF.
     *
     * Sends a request to the API to get the document's file,
     * reads the file from temporary storage, deletes the temp file,
     * and returns the binary content as a string.
     */
    private function downloadDocument(
        ApiClient $apiClient,
        string $documentId
    ): string {
        $downloadDoc = new DocumentDownloadGet();
        $downloadDoc->withDocumentId($documentId);

        $response = $apiClient->send($downloadDoc);
        /** @var DocumentDownloadGetResponse $response */

        $content = file_get_contents($response->getFile()->getRealPath());

        unlink($response->getFile()->getRealPath());

        return $content;
    }
}
