<?php

declare(strict_types=1);

namespace Samples\EmbeddedSenderWithFormDG;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\DocumentGroup\Request\DocumentGroupGet;
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsGet;
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsPut;
use SignNow\Api\DocumentGroup\Request\DownloadDocumentGroupPost;
use SignNow\Api\DocumentGroup\Response\DocumentGroupGet as DocumentGroupGetResponse;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsGet as DocumentGroupRecipientsGetResponse;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsPut as DocumentGroupRecipientsPutResponse;
use SignNow\Api\DocumentGroup\Response\DownloadDocumentGroupPost as DownloadDocumentGroupPostResponse;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\DocumentField\Request\Data\Field as FieldValue;
use SignNow\Api\DocumentField\Request\Data\FieldCollection as FieldValueCollection;
use SignNow\Api\DocumentField\Request\DocumentPrefillPut;
use SignNow\Api\DocumentGroupTemplate\Request\DocumentGroupTemplatePost;
use SignNow\Api\DocumentGroupTemplate\Response\DocumentGroupTemplatePost as DocumentGroupTemplatePostResponse;
use SignNow\Api\EmbeddedSending\Request\DocumentGroupEmbeddedSendingLinkPost;
use SignNow\Api\EmbeddedSending\Response\DocumentGroupEmbeddedSendingLinkPost
    as DocumentGroupEmbeddedSendingLinkPostResponse;
use SignNow\Api\DocumentGroup\Request\Data\Recipient\Recipient;
use SignNow\Api\DocumentGroup\Request\Data\Recipient\Document as RecipientDocument;
use SignNow\Api\DocumentGroup\Request\Data\Recipient\DocumentCollection
    as RecipientDocumentCollection;
use SignNow\ApiClient;
use SignNow\Sdk;
use Symfony\Component\HttpFoundation\Response;
use SignNow\Api\DocumentGroupInvite\Response\GroupInviteGet as GroupInviteGetResponse;
use SignNow\Api\DocumentGroupInvite\Request\GroupInviteGet;
use SplFileInfo;

class SampleController implements SampleControllerInterface
{
    /**
     * Document Group Template ID (DGT ID) for cloning a document group.
     */
    private const DOCUMENT_GROUP_TEMPLATE_ID = 'c8040bbc40804b89b63a0fa8c79b42a7ae4818c1';

    public function handleGet(Request $request): Response
    {
        return new Response(
            view('EmbeddedSenderWithFormDG::index')->render(),
            200,
            [
                'Content-Type' => 'text/html',
            ]
        );
    }

    public function handlePost(Request $request): Response
    {
        $action = $request->input('action');

        $sdk = new Sdk();
        $apiClient = $sdk->build()
            ->authenticate()
            ->getApiClient();

        switch ($action) {
            case 'prepare_dg':
                return $this->prepareDocumentGroup($request, $apiClient);
            case 'invite-status':
                return $this->getInviteStatus($request, $apiClient);
            case 'download-doc-group':
                return $this->downloadDocumentGroup($request, $apiClient);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    private function prepareDocumentGroup(Request $request, ApiClient $apiClient): JsonResponse
    {
        $name = $request->input('name');
        $email = $request->input('email');

        if (!$name || !$email) {
            return response()->json(['success' => false, 'message' => 'Name and email are required'], 400);
        }

        // 1. Create Document Group from Template
        $dgResponse = $this->createDocumentGroupFromTemplate($apiClient);
        if (!$dgResponse['success']) {
            return response()->json($dgResponse, 500);
        }

        $documentGroupId = $dgResponse['document_group_id'];

        // 2. Update document fields with names
        $updateFieldsResponse = $this->updateDocumentFields($apiClient, $documentGroupId, $name);
        if (!$updateFieldsResponse['success']) {
            return response()->json($updateFieldsResponse, 500);
        }

        // 3. Add recipients to Document Group with different emails for different roles
        $customerEmail = $email; // Email from form for "Customer to Sign"
        $preparerEmail = config('signnow.api.signer_email'); // Email from config for "Prepare Contract"
        $addRecipientsResponse = $this->updateDocumentGroupRecipients(
            $apiClient,
            $documentGroupId,
            $customerEmail,
            $preparerEmail
        );
        if (!$addRecipientsResponse['success']) {
            return response()->json($addRecipientsResponse, 500);
        }

        // 4. Create embedded sending link
        $embeddedSendingResponse = $this->createEmbeddedSendingUrl($apiClient, $documentGroupId);
        if (!$embeddedSendingResponse['success']) {
            return response()->json($embeddedSendingResponse, 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document group prepared and embedded sending link created successfully',
            'embedded_url' => $embeddedSendingResponse['embedded_url']
        ]);
    }



    private function getInviteStatus(Request $request, ApiClient $apiClient): JsonResponse
    {
        $documentGroupId = $request->input('document_group_id');
        $signers = $this->getDocumentGroupSignersStatus($apiClient, $documentGroupId);
        return response()->json($signers);
    }

    private function downloadDocumentGroup(Request $request, ApiClient $apiClient): Response
    {
        $documentGroupId = $request->input('document_group_id');

        $file = $this->downloadDocumentGroupFile($apiClient, $documentGroupId);

        $content = file_get_contents($file->getRealPath());
        unlink($file->getRealPath());

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $file->getFilename() . '"'
        ]);
    }

    private function createDocumentGroupFromTemplate(ApiClient $apiClient): array
    {
        // Create document group directly from Document Group Template
        $documentGroupTemplatePost = new DocumentGroupTemplatePost(
            groupName: 'ISV Form Document Group'
        );
        $documentGroupTemplatePost->withTemplateGroupId(self::DOCUMENT_GROUP_TEMPLATE_ID);

        /** @var DocumentGroupTemplatePostResponse $response */
        $response = $apiClient->send($documentGroupTemplatePost);

        return [
            'success' => true,
            'document_group_id' => $response->getData()->getUniqueId()
        ];
    }

    private function updateDocumentFields(ApiClient $apiClient, string $documentGroupId, string $name): array
    {
        // Fetch group info to get each doc ID
        $docGroup = $this->getDocumentGroup($apiClient, $documentGroupId);

        // For each document in the group, check if that doc has the Name field. Then fill it if found.
        foreach ($docGroup->getDocuments() as $docItem) {
            $docId = $docItem->getId();
            $documentData = $this->getDocument($apiClient, $docId);

            $existingFields = [];
            foreach ($documentData->getFields() as $field) {
                $existingFields[] = $field->getJsonAttributes()->getName();
            }

            // Build a field collection with only valid fields
            $fieldValues = new FieldValueCollection([]);

            // Try to fill "CustomerName" field first, then "CustomerFN" as fallback
            if (in_array('Name', $existingFields)) {
                $fieldValues->add(new FieldValue(
                    fieldName: 'Name',
                    prefilledText: $name
                ));
            }

            // If there are any fields to fill, send the request
            if (!$fieldValues->isEmpty()) {
                $prefillRequest = new DocumentPrefillPut($fieldValues);
                $prefillRequest->withDocumentId($docId);
                $apiClient->send($prefillRequest);
            }
        }

        return ['success' => true];
    }

    private function updateDocumentGroupRecipients(
        ApiClient $apiClient,
        string $documentGroupId,
        string $customerEmail,
        string $preparerEmail
    ): array {
        // Get current recipients
        $recipientsResponse = $this->getDocumentGroupRecipients($apiClient, $documentGroupId);
        $currentRecipients = $recipientsResponse->getData()->getRecipients();

        // Create updated recipients with customer email for all roles
        $updatedRecipients = [];
        foreach ($currentRecipients as $recipient) {
            $recipientName = $recipient->getName();

            // Convert Response DocumentCollection to Request DocumentCollection
            $requestDocuments = [];
            foreach ($recipient->getDocuments() as $document) {
                $requestDocuments[] = new RecipientDocument(
                    id: $document->getId(),
                    role: $document->getRole(),
                    action: $document->getAction()
                );
            }
            $requestDocumentCollection = new RecipientDocumentCollection(
                $requestDocuments
            );

            // Assign email based on recipient role/name
            if ($recipientName === 'Recipient 1') {
                $updatedRecipients[] = new Recipient(
                    name: $recipientName,
                    email: $preparerEmail,
                    order: $recipient->getOrder(),
                    documents: $requestDocumentCollection
                );
            } elseif ($recipientName === 'Recipient 2') {
                $updatedRecipients[] = new Recipient(
                    name: $recipientName,
                    email: $customerEmail,
                    order: $recipient->getOrder(),
                    documents: $requestDocumentCollection
                );
            } else {
                $updatedRecipients[] = new Recipient(
                    name: $recipientName,
                    email: '',
                    order: $recipient->getOrder(),
                    documents: $requestDocumentCollection
                );
            }
        }

        $recipientsCollection = new \SignNow\Api\DocumentGroup\Request\Data\Recipient\RecipientCollection(
            $updatedRecipients
        );
        $ccCollection = new \SignNow\Api\DocumentGroup\Request\Data\CcCollection([]); // Empty CC collection

        $updateRequest = new DocumentGroupRecipientsPut(
            recipients: $recipientsCollection,
            cc: $ccCollection
        );
        $updateRequest->withDocumentGroupId($documentGroupId);

        $apiClient->send($updateRequest);

        return ['success' => true];
    }

    private function createEmbeddedSendingUrl(ApiClient $apiClient, string $documentGroupId): array
    {
        // Return URL after embedded sending to redirect to status page
        $redirectUrl = config('app.url')
            . '/samples/EmbeddedSenderWithFormDG?' .
            http_build_query([
                'page' => 'status-page',
                'document_group_id' => $documentGroupId,
            ]);

        $embeddedSendingRequest = new DocumentGroupEmbeddedSendingLinkPost(
            redirectUri: $redirectUrl,
            redirectTarget: 'self',
            linkExpiration: 15, // 15 minutes
            type: 'send-invite'
        );
        $embeddedSendingRequest->withDocumentGroupId($documentGroupId);

        /** @var DocumentGroupEmbeddedSendingLinkPostResponse $response */
        $response = $apiClient->send($embeddedSendingRequest);

        // Get the embedded URL from response
        $embeddedUrl = $response->getData()->getUrl();

        return [
            'success' => true,
            'embedded_url' => $embeddedUrl
        ];
    }

    private function getDocumentGroup(ApiClient $apiClient, string $documentGroupId): DocumentGroupGetResponse
    {
        $request = new DocumentGroupGet();
        $request->withDocumentGroupId($documentGroupId);
        return $apiClient->send($request);
    }

    private function getDocument(ApiClient $apiClient, string $documentId): DocumentGetResponse
    {
        $request = new DocumentGet();
        $request->withDocumentId($documentId);
        return $apiClient->send($request);
    }

    private function getDocumentGroupRecipients(
        ApiClient $apiClient,
        string $documentGroupId
    ): DocumentGroupRecipientsGetResponse {
        $recipientsRequest = (new DocumentGroupRecipientsGet())
            ->withDocumentGroupId($documentGroupId);

        /** @var DocumentGroupRecipientsGetResponse $response */
        $response = $apiClient->send($recipientsRequest);
        return $response;
    }

    /**
     * Get signers status for Document Group
     */
    private function getDocumentGroupSignersStatus(
        ApiClient $apiClient,
        string $documentGroupId
    ): array {
        $recipientsResponse = $this->getDocumentGroupRecipients($apiClient, $documentGroupId);
        $signers = [];

        // Get invite status for the document group
        $docGroup = $this->getDocumentGroup($apiClient, $documentGroupId);
        $inviteId = $docGroup->getInviteId();

        if (!$inviteId) {
            // If no invite exists, return basic recipient info
            foreach ($recipientsResponse->getData()->getRecipients() as $recipient) {
                $signers[] = [
                    'name' => $recipient->getName(),
                    'email' => $recipient->getEmail(),
                    'status' => 'not_invited',
                    'order' => $recipient->getOrder(),
                    'timestamp' => null
                ];
            }
            return $signers;
        }

        $inviteStatusRequest = (new GroupInviteGet())
            ->withDocumentGroupId($documentGroupId)
            ->withInviteId($inviteId);

        /** @var GroupInviteGetResponse $inviteStatusResponse */
        $inviteStatusResponse = $apiClient->send($inviteStatusRequest);

        $statuses = [];
        foreach ($inviteStatusResponse->getInvite()->getSteps() as $step) {
            /**@var Step $step*/
            foreach ($step->getActions() as $action) {
                /**@var Action $action*/
                $statuses[$action->getRoleName()] = $action->getStatus();
            }
        }

        foreach ($recipientsResponse->getData()->getRecipients() as $recipient) {
            $signers[] = [
                'name' => $recipient->getName(),
                'email' => $recipient->getEmail(),
                'status' => $statuses[$recipient->getName()] ?? 'unknown',
                'order' => $recipient->getOrder(),
                'timestamp' => null // Document Group doesn't provide individual timestamps
            ];
        }

        return $signers;
    }

    /**
     * Download the entire doc group as a merged PDF, once all are signed.
     */
    private function downloadDocumentGroupFile(
        ApiClient $apiClient,
        string $documentGroupId
    ): SplFileInfo {
        $downloadRequest = (new DownloadDocumentGroupPost(
            'merged',
            'no'
        ))->withDocumentGroupId($documentGroupId);

        /** @var DownloadDocumentGroupPostResponse $response */
        $response = $apiClient->send($downloadRequest);

        return $response->getFile();
    }
}
