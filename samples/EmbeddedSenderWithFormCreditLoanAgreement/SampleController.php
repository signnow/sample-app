<?php

declare(strict_types=1);

namespace Samples\EmbeddedSenderWithFormCreditLoanAgreement;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use SignNow\Sdk;
use SignNow\ApiClient;
use SignNow\Api\Template\Request\CloneTemplatePost;
use SignNow\Api\Template\Response\CloneTemplatePost as CloneTemplatePostResponse;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\Document\Request\DocumentDownloadGet;
use SignNow\Api\DocumentField\Request\DocumentPrefillPut;
use SignNow\Api\DocumentField\Request\Data\Field as FieldValue;
use SignNow\Api\DocumentField\Request\Data\FieldCollection as FieldValueCollection;
use SignNow\Api\EmbeddedSending\Request\DocumentEmbeddedSendingLinkPost as DocumentEmbeddedSendingLinkPostRequest;
use SignNow\Api\EmbeddedSending\Response\DocumentEmbeddedSendingLinkPost as DocumentEmbeddedSendingLinkPostResponse;

class SampleController implements SampleControllerInterface
{
    private const TEMPLATE_ID = 'de45a9a2a6014c2c8ac0a4d9057b17a2108e77e7';
    /**
     * Handle GET requests for the Credit Loan Agreement demo.
     *
     * Business context:
     * - Serves the initial form and subsequent pages in the “Embedded Signer With Form” flow.
     * - When no specific `page` parameter is provided, this method renders the entry point
     *   where users input their name, email, and proceed to configure recipients.
     *
     * Method behavior:
     * 1. Renders the Blade template `EmbeddedSenderWithFormCreditLoanAgreement::index`.
     * 2. Wraps the rendered HTML in a Symfony Response with status 200.
     * 3. Sets the `Content-Type` header to `text/html`.
     *
     * Returns:
     * - Response: HTTP response containing the HTML for the initial form page.
     */
    public function handleGet(Request $request): Response
    {
        return new Response(
            view('EmbeddedSenderWithFormCreditLoanAgreement::index')->render(),
            200,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * Handle POST requests for the Credit Loan Agreement demo.
     *
     * Business context:
     * - Manages three distinct actions in the embedded signer workflow:
     *   1. **create-embedded-invite**: Clones the template, pre-fills fields,
     *      and generates an embedded signing link for the sender.
     *   2. **invite-status**: Returns the current email invitation statuses for monitoring.
     *   3. **download** (default): Retrieves and returns the final signed PDF document.
     *
     * Method behavior:
     * 1. Reads the `action` parameter from the request.
     * 2. Initializes and authenticates the SignNow SDK client.
     * 3. Routes based on `action`:
     *    - **create-embedded-invite**:
     *        • Extracts form data (`full_name`) and uses the configured template ID.
     *        • Calls `createEmbeddedInviteAndReturnSendingLink()` to prepare the document and obtain a signing link.
     *        • Returns a JsonResponse with the embedded link for frontend redirection.
     *    - **invite-status**:
     *        • Reads `document_id` from query parameters.
     *        • Calls `getDocumentStatuses()` to fetch real-time statuses of each recipient invite.
     *        • Returns a JsonResponse containing an array of status records.
     *    - **default (download)**:
     *        • Calls `downloadDocument()` to fetch the flattened, signed PDF by document ID.
     *        • Returns a Response configured with PDF content and download headers.
     *
     * Returns:
     * - JsonResponse when handling invite creation or invite-status actions.
     * - Response with PDF binary data and appropriate headers for download.
     */
    public function handlePost(Request $request): Response
    {
        $action = $request->input('action');

        $sdk = new Sdk();
        $apiClient = $sdk->build()->authenticate()->getApiClient();

        if ($action === 'create-embedded-invite') {
            $fullName = $request->input('full_name');

            $link = $this->createEmbeddedInviteAndReturnSendingLink(
                $apiClient,
                self::TEMPLATE_ID,
                ['Name' => $fullName]
            );

            return new JsonResponse(['link' => $link]);
        } elseif ($action === 'invite-status') {
            $documentId = $request->get('document_id');
            $statusList = $this->getDocumentStatuses($apiClient, $documentId);
            return new JsonResponse($statusList);
        }

        $file = $this->downloadDocument($apiClient, $request->get('document_id'));
        return new Response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="result.pdf"',
        ]);
    }

    /**
     * Download the finalized signed PDF document for the given document ID.
     *
     * Business context:
     * - After all recipients complete signing, the final document must be provided to the user.
     * - This method retrieves the flattened (collapsed) PDF to ensure all form fields and signatures are embedded.
     *
     * Method behavior:
     * 1. Constructs a DocumentDownloadGet request for the specified document ID with type "collapsed".
     * 2. Sends the request via the authenticated SignNow API client.
     * 3. Reads the temporary PDF file into memory and removes the file from the filesystem.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated SignNow client for API communication.
     * - string $documentId: Unique identifier of the document to download.
     *
     * Returns:
     * - string: Raw PDF binary data ready for inclusion in an HTTP response.
     */
    private function downloadDocument(ApiClient $apiClient, string $documentId): string
    {
        $request = (new DocumentDownloadGet())
            ->withDocumentId($documentId)
            ->withType('collapsed');

        $response = $apiClient->send($request);
        $filePath = $response->getFile()->getRealPath();
        $content = file_get_contents($filePath);
        unlink($filePath);

        return $content;
    }

    /**
     * Clone a template, prefill fields, and generate an embedded signing link.
     *
     * Business context:
     * - Supports the “Embedded Signer With Form” flow by preparing a new document instance
     *   and embedding user-provided data before signing.
     * - Ensures the document is ready for the signer (sender as signer) with all required fields filled.
     *
     * Method behavior:
     * 1. Clones the template identified by `$templateId` into a fresh document instance.
     * 2. Prefills the cloned document’s fields using the provided `$fields` data.
     * 3. Generates and returns an embedded signing link for the prepared document.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated SignNow client for API operations.
     * - string $templateId: Identifier of the SignNow template to clone.
     * - array $fields: Key-value pairs mapping field names to their prefilled values.
     *
     * Returns:
     * - string: URL of the embedded signing session for the newly created document.
     */
    private function createEmbeddedInviteAndReturnSendingLink(
        ApiClient $apiClient,
        string $templateId,
        array $fields
    ): string {
        $document = $this->createDocumentFromTemplate($apiClient, $templateId);

        $this->prefillFields($apiClient, $document->getId(), $fields);

        return $this->getEmbeddedSendingLink($apiClient, $document->getId());
    }

    /**
     * Clone a new document instance from a specified template.
     *
     * Business context:
     * - Templates are reusable blueprints; each signing session must operate on its own
     *   document copy to avoid altering the original.
     *
     * Method behavior:
     * 1. Initializes a CloneTemplatePost request with the given `$templateId`.
     * 2. Sends the request through the SignNow API client.
     * 3. Returns the response containing metadata for the newly created document,
     *    including its unique document ID.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated client used to perform the API call.
     * - string $templateId: The SignNow template identifier to clone.
     *
     * Returns:
     * - CloneTemplatePostResponse: Contains the new document’s ID and related data.
     */
    private function createDocumentFromTemplate(ApiClient $apiClient, string $templateId): CloneTemplatePostResponse
    {
        $clone = new CloneTemplatePost();
        $clone->withTemplateId($templateId);

        return $apiClient->send($clone);
    }

    /**
     * Generate an embedded sending link for a cloned document.
     *
     * Business context:
     * - After preparing the document (cloning and pre-filling), the user must enter
     *   an embedded editor session to place fields and finalize recipient setup.
     * - The embedded link ensures the user is returned to the status page upon completion.
     *
     * Method behavior:
     * 1. Constructs a redirect URL pointing back to the send status page with the document ID.
     * 2. Creates a DocumentEmbeddedSendingLinkPost request specifying:
     *    - The signing mode (“document”)
     *    - The redirect URI after signing
     *    - The session timeout (in minutes)
     * 3. Sends the request via the SignNow API client to obtain the embedded editor URL.
     * 4. Updates the returned URL by injecting a fresh access token and setting the `embedded` parameter.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated SignNow client for API communication.
     * - string $documentId: Unique identifier of the document for which to generate the link.
     *
     * Returns:
     * - string: A fully formed embedded signing URL, ready for frontend redirection.
     */
    private function getEmbeddedSendingLink(ApiClient $apiClient, string $documentId): string
    {
        $redirectUrl = config('app.url') . '/samples/EmbeddedSenderWithFormCreditLoanAgreement?page=download-with-status&document_id='
            . $documentId;

        $request = new DocumentEmbeddedSendingLinkPostRequest('invite', $redirectUrl, 16);
        $request->withDocumentId($documentId);

        /** @var DocumentEmbeddedSendingLinkPostResponse $response */
        $response = $apiClient->send($request);
        return $response->getData()->toArray()['url'];
    }

    /**
     * Pre-fill document fields with user-provided values before embedded signing.
     *
     * Business context:
     * - Ensures that the cloned document carries over data collected from the form
     *   (e.g., Name, Email) so that the signer sees the correct information.
     * - Streamlines the signing process by auto-populating known fields.
     *
     * Method behavior:
     * 1. Initializes an empty FieldValueCollection.
     * 2. Iterates over the `$fieldsValue` array:
     *    - For each non-null value, creates a FieldValue with the corresponding field name.
     *    - Adds the FieldValue to the collection.
     * 3. Constructs a DocumentPrefillPut request with the populated collection.
     * 4. Sets the document ID on the request.
     * 5. Sends the request via the SignNow API client to apply the prefilled data.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated SignNow client used to send the prefill request.
     * - string $documentId: Unique identifier of the document to prefill.
     * - array $fieldsValue: Associative array mapping field names to their prefill values.
     *
     * Returns:
     * - void
     */
    private function prefillFields(ApiClient $apiClient, string $documentId, array $fieldsValue): void
    {
        $collection = new FieldValueCollection();

        foreach ($fieldsValue as $name => $value) {
            if ($value !== null) {
                $collection->add(new FieldValue(fieldName: $name, prefilledText: $value));
            }
        }

        $request = new DocumentPrefillPut($collection);
        $request->withDocumentId($documentId);
        $apiClient->send($request);
    }

    /**
     * Retrieve the current email invitation statuses for all recipients of a document.
     *
     * Business context:
     * - Enables the Send Status page to display real-time updates on each recipient’s
     *   invitation lifecycle (sent, viewed, signed).
     * - Supports user monitoring of the embedded signing workflow progress.
     *
     * Method behavior:
     * 1. Sends a DocumentGet request to fetch full document metadata, including field invites.
     * 2. Converts the invites collection into an array.
     * 3. Iterates through each invite entry:
     *    - Extracts the first available email status record (if any).
     *    - Formats the status timestamp as `YYYY-MM-DD HH:MM:SS`, defaulting to an empty string.
     *    - Maps fields into a simplified status array with:
     *        • `name`      => recipient email address
     *        • `timestamp` => formatted creation date of the first status event
     *        • `status`    => status string (e.g., "sent", "viewed", "signed"; "unknown" if missing)
     * 4. Returns the assembled list of status arrays for JSON serialization.
     *
     * Parameters:
     * - ApiClient $apiClient: Authenticated SignNow client for API communication.
     * - string    $documentId: Unique identifier of the document to query.
     *
     * Returns:
     * - array: List of associative arrays representing each recipient’s invite status.
     */
    private function getDocumentStatuses(ApiClient $apiClient, string $documentId): array
    {
        /** @var DocumentGetResponse $response */
        $response = $apiClient->send((new DocumentGet())->withDocumentId($documentId));
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
