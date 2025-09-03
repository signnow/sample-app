<?php

declare(strict_types=1);

namespace Samples\ISVWithFormAndOneClickSendMergeFields;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\Document\Request\Data\Field;
use SignNow\Api\DocumentGroup\Request\DocumentGroupGet;
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsGet;
use SignNow\Api\DocumentGroup\Request\DownloadDocumentGroupPost;
use SignNow\Api\DocumentGroup\Response\DocumentGroupGet as DocumentGroupGetResponse;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsGet as DocumentGroupRecipientsGetResponse;
use SignNow\Api\DocumentGroup\Response\DownloadDocumentGroupPost as DownloadDocumentGroupPostResponse;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\DocumentGroupTemplate\Request\DocumentGroupTemplatePost;
use SignNow\Api\DocumentGroupTemplate\Response\DocumentGroupTemplatePost as DocumentGroupTemplatePostResponse;
use SignNow\Api\DocumentGroupInvite\Request\GroupInvitePost;
use SignNow\Api\DocumentGroupInvite\Response\GroupInvitePost as GroupInvitePostResponse;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteStep;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteStepCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteAction;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteActionCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteEmail;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteEmailCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\CcCollection;
use SignNow\Api\DocumentGroupInvite\Request\GroupInviteGet;
use SignNow\Api\DocumentGroupInvite\Response\GroupInviteGet as GroupInviteGetResponse;
use SignNow\Api\Document\Request\DocumentPut;
use SignNow\Api\Document\Request\Data\FieldCollection;
use SignNow\Api\Document\Request\Data\TextCollection;
use SignNow\Api\Document\Request\Data\Text;
use SignNow\ApiClient;
use SignNow\Sdk;
use SplFileInfo;
use Symfony\Component\HttpFoundation\Response;

class SampleController implements SampleControllerInterface
{
    /**
     * Document Group Template ID (DGT ID) for cloning a document group.
     */
    private const DOCUMENT_GROUP_TEMPLATE_ID = '8e36720a436041ea837dc543ec00a3bc3559df45';

    public function handleGet(Request $request): Response
    {
        return new Response(
            view('ISVWithFormAndOneClickSendMergeFields::index')->render(),
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
        $customerName = $request->input('customer_name');
        $companyName = $request->input('company_name');
        $email = $request->input('email');

        if (!$customerName || !$companyName || !$email) {
            return response()->json([
                'success' => false,
                'message' => 'Customer name, company name and email are required'
            ], 400);
        }

        // 1. Create Document Group from Template
        $dgResponse = $this->createDocumentGroupFromTemplate($apiClient);
        if (!$dgResponse['success']) {
            return response()->json($dgResponse, 500);
        }

        $documentGroupId = $dgResponse['document_group_id'];

        // 2. Process merge fields with customer and company names
        $updateFieldsResponse = $this->processMergeFields($apiClient, $documentGroupId, $customerName, $companyName);
        if (!$updateFieldsResponse['success']) {
            return response()->json($updateFieldsResponse, 500);
        }

        // 3. Send invite to recipients
        $inviteResponse = $this->sendInvite($apiClient, $documentGroupId, $email);
        if (!$inviteResponse['success']) {
            return response()->json($inviteResponse, 500);
        }

        return response()->json([
            'success' => true,
            'document_group_id' => $documentGroupId
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

        if (!$documentGroupId) {
            return response()->json(['success' => false, 'message' => 'Document group ID is required'], 400);
        }

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

    /**
     * Process merge fields by adding permanent text elements to documents.
     *
     * This method processes merge fields (CustomerName and CompanyName) by creating
     * permanent text elements that become part of the document itself. These text
     * elements are read-only and cannot be modified by signers, making them
     * permanent part of the document content.
     *
     * The text elements are positioned at the same coordinates as the original
     * merge fields, effectively converting them into non-editable text content.
     */
    private function processMergeFields(
        ApiClient $apiClient,
        string $documentGroupId,
        string $customerName,
        string $companyName
    ): array {
        // Fetch group info to get each doc ID
        $docGroup = $this->getDocumentGroup($apiClient, $documentGroupId);

        // For each document in the group, add text elements instead of filling fields
        foreach ($docGroup->getDocuments() as $docItem) {
            $docId = $docItem->getId();
            $documentData = $this->getDocument($apiClient, $docId);

            // Create text collection with customer name and company name
            $textCollection = new TextCollection();
            $fieldCollection = new FieldCollection();

            // Get coordinates from existing fields
            $customerNameField = null;
            $companyNameField = null;

            foreach ($documentData->getFields() as $field) {
                /** @var \SignNow\Api\Document\Response\Data\Field $field */
                $fieldName = $field->getJsonAttributes()->getName();
                if ($fieldName === 'CustomerName') {
                    $customerNameField = $field;
                } elseif ($fieldName === 'CompanyName') {
                    $companyNameField = $field;
                } else {
                    // Create a new Field object for the request using data from response field
                    $fieldAttributes = $field->getJsonAttributes()->toArray();
                    $fieldAttributes['type'] = $field->getType();
                    $fieldAttributes['role'] = $field->getRole();

                    $requestField = \SignNow\Api\Document\Request\Data\Field::fromArray($fieldAttributes);
                    $fieldCollection->add($requestField);
                }
            }

            // Add customer name text at field coordinates if field exists
            if ($customerNameField) {
                $fieldCoords = $customerNameField->getJsonAttributes();
                $textCollection->add(new Text(
                    x: $fieldCoords->getX(),
                    y: $fieldCoords->getY(),
                    size: $fieldCoords->getSize() ?? 25,
                    width: $fieldCoords->getWidth(),
                    height: $fieldCoords->getHeight(),
                    subtype: 'text',
                    pageNumber: $fieldCoords->getPageNumber(),
                    data: $customerName,
                    font: 'Arial',
                    lineHeight: $fieldCoords->getSize() ?? 25
                ));
            }

            // Add company name text at field coordinates if field exists
            if ($companyNameField) {
                $fieldCoords = $companyNameField->getJsonAttributes();
                $textCollection->add(new Text(
                    x: $fieldCoords->getX(),
                    y: $fieldCoords->getY(),
                    size: $fieldCoords->getSize() ?? 20,
                    width: $fieldCoords->getWidth(),
                    height: $fieldCoords->getHeight(),
                    subtype: 'text',
                    pageNumber: $fieldCoords->getPageNumber(),
                    data: $companyName,
                    font: 'Arial',
                    lineHeight: $fieldCoords->getSize() ?? 20
                ));
            }

            // Create DocumentPut request with empty fields and text elements
            $documentPutRequest = new DocumentPut(
                fields: $fieldCollection,
                texts: $textCollection
            );
            $documentPutRequest->withDocumentId($docId);

            $apiClient->send($documentPutRequest);
        }

        return ['success' => true];
    }

    private function sendInvite(ApiClient $apiClient, string $documentGroupId, string $email): array
    {
        // Get document group to find documents
        $documentGroupGetResponse = $this->getDocumentGroup($apiClient, $documentGroupId);

        // Get recipients to use their roles
        $recipientsResponse = $this->getDocumentGroupRecipients($apiClient, $documentGroupId);
        $recipients = $recipientsResponse->getData()->getRecipients();

        // Check if we have recipients
        if (empty($recipients)) {
            return ['success' => false, 'message' => 'No recipients found in document group'];
        }

        // Create invite actions and emails for each recipient and document
        $inviteActions = [];
        $inviteEmails = [];
        foreach ($recipients as $recipient) {
            // Assign email based on recipient role/name
            $emailToUse = $email; // Default to form email
            if ($recipient->getName() === 'Prepare Contract') {
                $emailToUse = $email; // Use form email for "Prepare Contract"
            } elseif ($recipient->getName() === 'Customer to Sign') {
                // Use config email for "Customer to Sign"
                $emailToUse = config('signnow.api.signer_email');
            }

            // Create invite email for this recipient
            $inviteEmails[] = new InviteEmail(
                email: $emailToUse,
                subject: 'Review and sign documents',
                message: 'Please review and sign the documents',
                expirationDays: 30
            );

            foreach ($documentGroupGetResponse->getDocuments() as $document) {
                $inviteActions[] = new InviteAction(
                    email: $emailToUse,
                    roleName: $recipient->getName(),
                    action: 'sign',
                    documentId: $document->getId(),
                    allowReassign: '0',
                    declineBySignature: '0',
                    redirectUri: config('app.url') . '/samples/ISVWithFormAndOneClickSendMergeFields?page=status-page&document_group_id=' . $documentGroupId,
                    redirectTarget: 'self'
                );
            }
        }

        $inviteActionCollection = new InviteActionCollection($inviteActions);

        $inviteEmailCollection = new InviteEmailCollection($inviteEmails);

        // Create invite step
        $inviteStep = new InviteStep(
            order: 1,
            inviteActions: $inviteActionCollection,
            inviteEmails: $inviteEmailCollection
        );

        $inviteStepCollection = new InviteStepCollection([$inviteStep]);

        // Create empty collections
        $ccCollection = new CcCollection([]);

        // Create and send invite request
        $inviteRequest = new GroupInvitePost(
            inviteSteps: $inviteStepCollection,
            cc: $ccCollection,
            signAsMerged: true
        );
        $inviteRequest->withDocumentGroupId($documentGroupId);

        /** @var GroupInvitePostResponse $response */
        $apiClient->send($inviteRequest);

        return [
            'success' => true
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
            /**@var \SignNow\Api\DocumentGroupInvite\Response\Data\Step $step*/
            foreach ($step->getActions() as $action) {
                /**@var \SignNow\Api\DocumentGroupInvite\Response\Data\Action $action*/
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
