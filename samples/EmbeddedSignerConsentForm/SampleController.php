<?php

declare(strict_types=1);

namespace Samples\EmbeddedSignerConsentForm;

use App\Http\Controllers\SampleControllerInterface;
use GuzzleHttp\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SampleController implements SampleControllerInterface
{
    private const TEMPLATE_ID = '3d28c78de8ec43ccab81a3e7dde07925cb5a1d29';

    /**
     * Handles GET requests to the sample route.
     *
     * If the `page` parameter is set to 'download-container', it returns an HTML page
     * indicating the document was sent and providing a download button.
     * Otherwise, it initiates the signing flow by cloning the template,
     * creating an embedded invite, and redirecting the user to the embedded signing link.
     */
    public function handleGet(Request $request): Response
    {
        $page = $request->get('page');

        if ($page === 'download-container') {
            return new Response(
                view('EmbeddedSignerConsentForm::index')->render(),
                200,
                [
                    'Content-Type' => 'text/html',
                ]
            );
        }

        $token = $this->authenticate();
        $client = $this->buildClient($token);

        $link = $this->createEmbeddedSenderAndReturnSigningLink($client, self::TEMPLATE_ID);

        return new RedirectResponse($link);
    }

    /**
     * Handles POST requests to download a completed document.
     *
     * Expects a `document_id` parameter in the request.
     * Downloads the document by its ID and returns it as a PDF file response.
     */
    public function handlePost(Request $request): Response
    {
        $documentId = $request->get('document_id');

        $token = $this->authenticate();
        $client = $this->buildClient($token);

        $file = $this->downloadDocument($client, $documentId);

        return new Response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="result.pdf"',
        ]);
    }

    /**
     * Orchestrates the process of generating an embedded invite signing link.
     *
     * Steps:
     * 1. Clones the document from a template.
     * 2. Retrieves the signer's role ID from the cloned document.
     * 3. Creates an embedded invite for the signer.
     * 4. Generates and returns the embedded signing link with a redirect back to the download page.
     */
    private function createEmbeddedSenderAndReturnSigningLink(
        Client $client,
        string $templateId,
    ): string {
        $cloneTemplateResponse = $this->createDocumentFromTemplate(
            $client,
            $templateId,
        );

        $signerEmail = config('signnow.api.signer_email');

        $roleId = $this->getSignerUniqueRoleId($client, $cloneTemplateResponse['id'], 'Recipient 1');

        $documentInviteResponse = $this->createEmbeddedInviteForOneSigner(
            $client,
            $cloneTemplateResponse['id'],
            $signerEmail,
            $roleId
        );

        return $this->getEmbeddedInviteLink(
            $client,
            $cloneTemplateResponse['id'],
            $documentInviteResponse['data'][0]['id']
        );
    }

    /**
     * Sends a request to the SignNow API to create a document from a template.
     *
     * Returns an array containing the new document ID.
     */
    private function createDocumentFromTemplate(
        Client $client,
        string $templateId,
    ): array {
        $response = $client->post("/template/{$templateId}/copy", [
            'json' => new \stdClass(),
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Generates a URL link for the embedded signing session.
     *
     * Takes the document ID and invite ID, then builds a redirect URL
     * to the 'download-container' page, which will be used after signing is completed.
     */
    private function getEmbeddedInviteLink(
        Client $client,
        string $documentId,
        string $inviteId,
    ): string {
        $response = $client->post("/v2/documents/{$documentId}/embedded-invites/{$inviteId}/link", [
            'json' => [
                'auth_method' => 'none',
                'link_expiration' => 15,
            ],
        ]);

        $embeddedInvite = json_decode((string) $response->getBody(), true);

        $redirectUrl = config('app.url')
            . '/samples/EmbeddedSignerConsentForm?page=download-container&document_id='
            . $documentId;

        return $embeddedInvite['data']['link'] . '&redirect_uri=' . urlencode($redirectUrl);
    }

    /**
     * Sends a request to create an embedded invite for a single signer.
     *
     * Accepts the document ID, signer email, and role ID.
     * Returns an array containing invite data.
     */
    private function createEmbeddedInviteForOneSigner(
        Client $client,
        string $documentId,
        string $signerEmail,
        string $roleId,
    ): array {
        $response = $client->post("/v2/documents/{$documentId}/embedded-invites", [
            'json' => [
                'invites' => [
                    [
                        'email' => $signerEmail,
                        'role_id' => $roleId,
                        'order' => 1,
                        'auth_method' => 'none',
                    ],
                ],
            ],
        ]);

        return json_decode((string) $response->getBody(), true);
    }

    /**
     * Retrieves the unique role ID for the signer from the cloned document.
     *
     * It looks for a role by name (e.g., "Recipient 1") and returns its unique ID,
     * which is required to assign the signer to the correct role in the invite.
     */
    private function getSignerUniqueRoleId(
        Client $client,
        string $documentId,
        string $signerRole
    ): string {
        $response = $client->get("/document/{$documentId}");
        $document = json_decode((string) $response->getBody(), true);

        $roleUniqueId = '';
        foreach ($document['roles'] as $role) {
            if ($role['name'] === $signerRole) {
                $roleUniqueId = $role['unique_id'];
                break;
            }
        }

        return $roleUniqueId;
    }

    /**
     * Downloads the finalized signed document as a PDF.
     *
     * Sends a request to the API to get the document's file
     * and returns the binary content as a string.
     */
    private function downloadDocument(
        Client $client,
        string $documentId
    ): string {
        $response = $client->get("/document/{$documentId}/download", [
            'query' => ['type' => 'collapsed'],
        ]);

        return (string) $response->getBody();
    }

    /**
     * Authenticates against SignNow API and returns access token.
     */
    private function authenticate(): string
    {
        $client = new Client(['base_uri' => config('signnow.api.host')]);

        $response = $client->post('/oauth2/token', [
            'headers' => [
                'Authorization' => 'Basic ' . config('signnow.api.basic_token'),
            ],
            'form_params' => [
                'grant_type' => 'password',
                'username' => config('signnow.api.user'),
                'password' => config('signnow.api.password'),
            ],
        ]);

        $data = json_decode((string) $response->getBody(), true);

        return $data['access_token'];
    }

    private function buildClient(string $token): Client
    {
        return new Client([
            'base_uri' => config('signnow.api.host'),
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}
