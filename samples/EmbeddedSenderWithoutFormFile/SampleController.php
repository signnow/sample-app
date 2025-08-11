<?php

declare(strict_types=1);

namespace Samples\EmbeddedSenderWithoutFormFile;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use SignNow\Api\Document\Request\DocumentDownloadGet;
use SignNow\Api\Document\Response\DocumentDownloadGet as DocumentDownloadGetResponse;
use SignNow\Api\EmbeddedSending\Request\DocumentEmbeddedSendingLinkPost as DocumentEmbeddedSendingLinkPostRequest;
use SignNow\Api\EmbeddedSending\Response\DocumentEmbeddedSendingLinkPost;
use Symfony\Component\HttpFoundation\Response;
use SignNow\ApiClient;
use SignNow\Sdk;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\Template\Request\CloneTemplatePost;
use SignNow\Api\Template\Response\CloneTemplatePost as CloneTemplatePostResponse;

class SampleController implements SampleControllerInterface
{
    /**
     * Template ID used to create documents.
     */
    private const TEMPLATE_ID = '76713f00c106425ea8b673c49fd94c0145643c34';

    /**
     * Handle incoming POST requests and route them based on the `action` parameter.
     *
     * Business context:
     * - In this embedded sending demo, the frontend first polls for invite statuses
     *   (Send Status step) and then downloads the signed document once signing is complete (Finish step).
     *
     * Method behavior:
     * 1. Extracts the `action` from the request:
     *    - When `action === 'invite-status'`, retrieves the current list of recipient invite statuses
     *      and returns it as JSON.
     *    - For any other action (e.g., download), returns the signed PDF for download.
     * 2. Initializes an authenticated SignNow API client to perform necessary operations.
     * 3. Uses `document_id` to identify which document to query or download.
     *
     * Returns:
     * - JsonResponse containing the array of invite status objects when the frontend requests status.
     * - Response with PDF content and download headers when the frontend requests the completed document.
     */
    public function handlePost(Request $request): Response
    {
        $action = $request->input('action');
        $sdk = new Sdk();
        /** @var ApiClient $apiClient */
        $apiClient = $sdk->build()->authenticate()->getApiClient();
        $documentId = $request->get('document_id');
        if ($action === 'invite-status') {
            $statusList = $this->getDocumentStatuses($apiClient, $documentId);
            return new JsonResponse($statusList);
        } else {
            $file = $this->downloadDocument($apiClient, $documentId);

            return new Response($file, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="document.pdf"',
            ]);
        }
    }

    /**
     * Handle incoming GET requests and direct the flow according to the `page` parameter.
     *
     * Business context:
     * - Entry point for the embedded sending flow: either initiates the embedded editor session
     *   or displays the send status page for an already-created document.
     *
     * Method behavior:
     * 1. Reads `page` and `document_id` from the query string.
     * 2. Authenticates a SignNow API client for subsequent operations.
     * 3. If `page === 'download-with-status'`, fetches the current invite statuses
     *    and renders the status monitoring page.
     * 4. Otherwise, starts a new embedded sending session by cloning a template
     *    and redirecting the user into the SignNow embedded editor.
     *
     * Returns:
     * - Response containing the rendered HTML of the send status page when monitoring statuses.
     * - RedirectResponse to the SignNow embedded editor for new document preparation.
     */
    public function handleGet(Request $request): Response
    {
        $page = $request->get('page');
        $documentId = $request->get('document_id');
        $sdk = new Sdk();
        /** @var ApiClient $apiClient */
        $apiClient = $sdk->build()->authenticate()->getApiClient();

        if ($page === 'download-with-status') {
            return new Response(
                view('EmbeddedSenderWithoutFormFile::index')->render(),
                200,
                ['Content-Type' => 'text/html']
            );
        }

        $url = $this->getEmbeddedSendingLink($apiClient);
        return new RedirectResponse($url);
    }

    /**
     * Clone a new document instance from a predefined template.
     *
     * Business context:
     * - Templates cannot be sent directly for signing; they must first be cloned
     *   into a working document instance.
     * - This step ensures each signing session operates on its own document copy.
     *
     * Method behavior:
     * 1. Prepares a CloneTemplatePost request with the configured template ID.
     * 2. Sends the request via the authenticated ApiClient.
     * 3. Returns the response, which includes the new document ID.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated client used to perform the API call.
     *
     * Returns:
     * - CloneTemplatePostResponse: Contains metadata about the cloned document,
     *   including its unique ID for subsequent operations.
     */
    private function createDocumentFromTemplate(ApiClient $apiClient): CloneTemplatePostResponse
    {
        $cloneTemplate = (new CloneTemplatePost())
            ->withTemplateId(self::TEMPLATE_ID);

        return $apiClient->send($cloneTemplate);
    }

    /**
     * Generate an embedded sending link
     *
     * Business context:
     * - After cloning a document from the template, the user must place fields and recipients
     *   within an embedded editor session.
     * - This method initiates that session and ensures the user returns to the status page afterward.
     *
     * Method behavior:
     * 1. Clones a fresh document instance via createDocumentFromTemplate().
     * 2. Constructs a redirect URL pointing back to the send status page, including the new document ID.
     * 3. Prepares a DocumentEmbeddedSendingLinkPost request with the cloned document ID and the redirect URI.
     * 4. Sends the embedded sending request to obtain the editor URL.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated SignNow client for API communication.
     *
     * Returns:
     * - string: Embedded sending link
     */
    private function getEmbeddedSendingLink(ApiClient $apiClient): string
    {
        $cloneResponse = $this->createDocumentFromTemplate($apiClient);
        $documentId = $cloneResponse->getId();

        $redirectUrl = config('app.url') . '/samples/EmbeddedSenderWithoutFormFile?page=download-with-status&document_id='
            . $documentId;

        $embeddedRequest = new DocumentEmbeddedSendingLinkPostRequest('document', $redirectUrl, 16);
        $embeddedRequest->withDocumentId($documentId);

        /** @var DocumentEmbeddedSendingLinkPost $response */
        $response = $apiClient->send($embeddedRequest);
        return $response->getData()->toArray()['url'];
    }

    /**
     * Retrieve the signed PDF content for a given document ID.
     *
     * Business context:
     * - After recipients complete signing, the final PDF must be made available
     *   for download in the Finish step.
     *
     * Method behavior:
     * 1. Constructs a DocumentDownloadGet request with the specified document ID.
     * 2. Requests a "collapsed" PDF to flatten all fields and signatures.
     * 3. Sends the request using the authenticated ApiClient.
     * 4. Reads the temporary file content into a string.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated SignNow client to perform the download.
     * - string $documentId: Unique identifier of the signed document in SignNow.
     *
     * Returns:
     * - string: Raw PDF binary data ready to be sent in a download response.
     */
    private function downloadDocument(ApiClient $apiClient, string $documentId): string
    {
        $downloadDoc = new DocumentDownloadGet();
        $downloadDoc->withDocumentId($documentId);
        $downloadDoc->withType('collapsed');

        /** @var DocumentDownloadGetResponse $response */
        $response = $apiClient->send($downloadDoc);
        $content = file_get_contents($response->getFile()->getRealPath());
        $content = file_get_contents($response->getFile()->getRealPath());

        unlink($response->getFile()->getRealPath());

        return $content;
    }

    /**
     * Fetch and parse the invite statuses for all recipients of a document.
     *
     * Business context:
     * - During the Send Status step, the frontend needs up-to-date information
     *   on each recipient’s email invitation (e.g., whether the invite was sent, viewed, or signed).
     * - This enables the user to monitor the progress of the signing workflow in real time.
     *
     * Method behavior:
     * 1. Sends a DocumentGet request to retrieve the full document metadata, including field invites.
     * 2. Extracts the array of invite objects from the response.
     * 3. For each invite, reads the first available email status entry:
     *    - Maps the recipient’s email address (`name`) and the status timestamp.
     *    - Formats the timestamp as `YYYY-MM-DD HH:MM:SS`, or leaves it blank if unavailable.
     *    - Sets a default status of `"unknown"` when no status data exists.
     * 4. Aggregates these into a simple array of status records for JSON serialization.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated SignNow client to perform the document query.
     * - string $documentId: Unique identifier of the document whose invite statuses are requested.
     *
     * Returns:
     * - array: List of associative arrays, each containing:
     *   - `name`      => recipient email address
     *   - `timestamp` => formatted creation date of the first status update
     *   - `status`    => human-readable status string (e.g., "sent", "viewed", "signed")
     */
    private function getDocumentStatuses(ApiClient $apiClient, string $documentId): array
    {
        $request = (new DocumentGet())->withDocumentId($documentId);

        /** @var DocumentGetResponse$response */
        $response = $apiClient->send($request);
        $invites = $response->getFieldInvites()->toArray();

        $statuses = [];
        foreach ($invites as $invite) {
            $statuses[] = [
                'name' => $invite['email'] ?? '',
                'timestamp' => $invite ? date('Y-m-d H:i:s', (int)$invite['updated']) : '',
                'status' => $invite['status'],
            ];
        }
        return $statuses;
    }
}
