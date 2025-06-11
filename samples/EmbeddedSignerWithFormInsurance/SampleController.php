<?php

declare(strict_types=1);

namespace Samples\EmbeddedSignerWithFormInsurance;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\Document\Request\DocumentDownloadGet;
use SignNow\Api\Template\Request\CloneTemplatePost;
use SignNow\Api\Template\Response\CloneTemplatePost as CloneTemplatePostResponse;
use Symfony\Component\HttpFoundation\Response;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Response\Data\Role;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\DocumentField\Request\Data\Field as FieldValue;
use SignNow\Api\DocumentField\Request\Data\FieldCollection as FieldValueCollection;
use SignNow\Api\DocumentField\Request\DocumentPrefillPut;
use SignNow\Api\EmbeddedInvite\Request\Data\Invite;
use SignNow\Api\EmbeddedInvite\Request\Data\InviteCollection;
use SignNow\Api\EmbeddedInvite\Request\DocumentInviteLinkPost;
use SignNow\Api\EmbeddedInvite\Request\DocumentInvitePost as DocumentInvitePostRequest;
use SignNow\Api\EmbeddedInvite\Response\DocumentInviteLinkPost as DocumentInviteLinkPostResponse;
use SignNow\Api\EmbeddedInvite\Response\DocumentInvitePost as DocumentInvitePostResponse;
use SignNow\ApiClient;
use SignNow\Sdk;
use SignNow\Api\Document\Response\DocumentDownloadGet as DocumentDownloadGetResponse;

class SampleController implements SampleControllerInterface
{
    private const TEMPLATE_ID = '60d8e92f12004fda8985d4574237507e6407530d';

    /**
     * Display the initial HTML form page to collect user's name and email.
     */
    public function handleGet(Request $request): Response
    {
        return new Response(
            view('EmbeddedSignerWithFormInsurance::index')->render(),
            200,
            [
                'Content-Type' => 'text/html',
            ]
        );
    }

    /**
     * Handle POST requests:
     * - If action is 'create-embedded-invite', clones template, pre-fills fields,
     *   creates embedded invite, and returns signing link.
     * - Otherwise, treats as a request to download the signed PDF document.
     */
    public function handlePost(Request $request): Response
    {
        $action = $request->input('action');

        $sdk = new Sdk();
        $apiClient = $sdk->build()
            ->authenticate()
            ->getApiClient();

        if ($action === 'create-embedded-invite') {
            $fullName = $request->input('full_name');
            $email = $request->input('email');

            $link = $this->createEmbeddedInviteAndReturnSigningLink(
                $apiClient,
                self::TEMPLATE_ID,
                [
                    'Name' => $fullName,
                    'Email' => $email,
                ]
            );

            return new JsonResponse(['link' => $link]);
        } else {
            $file = $this->downloadDocument($apiClient, $request->get('document_id'));

            return new Response($file, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="result.pdf"',
            ]);
        }
    }

    /**
     * Downloads the signed document PDF file using its ID.
     * The file is temporarily retrieved and then removed after reading its content.
     */
    private function downloadDocument(
        ApiClient $apiClient,
        string $documentId
    ): string {
        $downloadDoc = new DocumentDownloadGet();
        $downloadDoc->withDocumentId($documentId);
        $downloadDoc->withType('collapsed');

        /** @var DocumentDownloadGetResponse $response */
        $response = $apiClient->send($downloadDoc);

        $content = file_get_contents($response->getFile()->getRealPath());

        unlink($response->getFile()->getRealPath());

        return $content;
    }

    /**
     * Clones the template to create a new document instance.
     * Only documents (not templates) can be sent for signing, so we must clone.
     * After cloning, pre-fills fields and creates embedded signing invite.
     * Finally, returns the signing link.
     */
    private function createEmbeddedInviteAndReturnSigningLink(
        ApiClient $apiClient,
        string $templateId,
        array $fields,
    ): string {
        $cloneTemplateResponse = $this->createDocumentFromTemplate(
            $apiClient,
            $templateId,
        );

        $this->prefillFields(
            $apiClient,
            $cloneTemplateResponse->getId(),
            $fields
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
     * Sends a request to clone the template into a new document.
     * Required because only documents (not templates) can be signed.
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
     * Retrieves the embedded signing link for a specific document and invite.
     * This allows the signer to access and sign the document within the app.
     */
    private function getEmbeddedInviteLink(
        ApiClient $apiClient,
        string $documentId,
        string $inviteId,
    ): string {
        $embeddedInvite = new DocumentInviteLinkPost("none", 15);
        $embeddedInvite->withFieldInviteId($inviteId);
        $embeddedInvite->withDocumentId($documentId);

        /**@var DocumentInviteLinkPostResponse $embeddedInviteRe*/
        $embeddedInviteRe = $apiClient->send($embeddedInvite);

        $redirectUrl = config('app.url')
            . '/samples/EmbeddedSignerWithFormInsurance?page=download-container&document_id='
            . $documentId;

        return $embeddedInviteRe->getData()->getLink() . '&redirect_uri=' . urlencode($redirectUrl);
    }

    /**
     * Pre-fills the document fields with user-provided values (e.g. Name, Email).
     * This step ensures the signer sees the filled-in fields before signing.
     */
    private function prefillFields(
        ApiClient $apiClient,
        string $documentId,
        array $fieldsValue
    ): void {
        $fields = new FieldValueCollection([]);

        foreach ($fieldsValue as $fieldName => $fieldValue) {
            if ($fieldValue !== null) {
                $fields->add(
                    new FieldValue(
                        fieldName: $fieldName,
                        prefilledText: $fieldValue,
                    )
                );
            }
        }

        $patchFields = new DocumentPrefillPut($fields);
        $patchFields->withDocumentId($documentId);
        $apiClient->send($patchFields);
    }

    /**
     * Creates an embedded invite (required for embedded signing) for one signer.
     * Uses the signer's email and the role ID from the template.
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
     * Fetches the unique role ID of a signer from the document by role name.
     * Needed to properly assign the signer in the embedded invite request.
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
}
