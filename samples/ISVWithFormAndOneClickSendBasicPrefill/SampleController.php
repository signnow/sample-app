<?php

declare(strict_types=1);

namespace Samples\ISVWithFormAndOneClickSendBasicPrefill;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\DocumentGroup\Request\DocumentGroupGet;
use SignNow\Api\DocumentGroup\Request\DownloadDocumentGroupPost;
use SignNow\Api\DocumentGroup\Response\DocumentGroupGet as DocumentGroupGetResponse;
use SignNow\Api\DocumentGroup\Response\DownloadDocumentGroupPost as DownloadDocumentGroupPostResponse;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\DocumentField\Request\Data\Field as FieldValue;
use SignNow\Api\DocumentField\Request\Data\FieldCollection as FieldValueCollection;
use SignNow\Api\DocumentField\Request\DocumentPrefillPut;
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
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsGet;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsGet as DocumentGroupRecipientsGetResponse;
use SignNow\Api\DocumentGroupInvite\Request\GroupInviteGet;
use SignNow\Api\DocumentGroupInvite\Response\GroupInviteGet as GroupInviteGetResponse;
use SignNow\ApiClient;
use SignNow\Sdk;
use Symfony\Component\HttpFoundation\Response;

class SampleController implements SampleControllerInterface
{
    /**
     * Document Group Template ID (DGT ID) for cloning a document group.
     */
    private const DOCUMENT_GROUP_TEMPLATE_ID = '6e79b9e6f9624984a7f054a7171d1644d0fb9934';

    public function handleGet(Request $request): Response
    {
        return new Response(
            view('ISVWithFormAndOneClickSendBasicPrefill::index')->render(),
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
        $updateFieldsResponse = $this->updateDocumentFields($apiClient, $documentGroupId, $name, $email);
        if (!$updateFieldsResponse['success']) {
            return response()->json($updateFieldsResponse, 500);
        }

        // 3. Send invite to the recipient
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

        $fileContent = $this->downloadDocumentGroupFile($apiClient, $documentGroupId);
        
        return new Response($fileContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="completed_document.pdf"'
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

    private function updateDocumentFields(ApiClient $apiClient, string $documentGroupId, string $name, string $email): array
    {
        // Fetch group info to get each doc ID
        $docGroup = $this->getDocumentGroup($apiClient, $documentGroupId);

        // For each document in the group, check if that doc has the Name and Email fields. Then fill them if found.
        foreach ($docGroup->getDocuments() as $docItem) {
            $docId = $docItem->getId();
            $documentData = $this->getDocument($apiClient, $docId);

            $existingFields = [];
            foreach ($documentData->getFields() as $field) {
                $existingFields[] = $field->getJsonAttributes()->getName();
            }

            // Build a field collection with only valid fields
            $fieldValues = new FieldValueCollection([]);

            // Try to fill "Name" field
            if (in_array('Name', $existingFields)) {
                $fieldValues->add(new FieldValue(
                    fieldName: 'Name',
                    prefilledText: $name
                ));
            }

            // Try to fill "Email" field
            if (in_array('Email', $existingFields)) {
                $fieldValues->add(new FieldValue(
                    fieldName: 'Email',
                    prefilledText: $email
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

    private function sendInvite(ApiClient $apiClient, string $documentGroupId, string $email): array
    {
        // Get document group to find documents
        $documentGroupGetResponse = $this->getDocumentGroup($apiClient, $documentGroupId);

        // Create invite actions for each document
        $inviteActions = [];
        foreach ($documentGroupGetResponse->getDocuments() as $document) {
            $inviteActions[] = new InviteAction(
                email: $email,
                roleName: 'Recipient 1',
                action: 'sign',
                documentId: $document->getId(),
                allowReassign: '0',
                declineBySignature: '0',
                redirectUri: config('app.url') . '/samples/ISVWithFormAndOneClickSendBasicPrefill?page=status-page&document_group_id=' . $documentGroupId,
                redirectTarget: 'self'
            );
        }

        $inviteActionCollection = new InviteActionCollection($inviteActions);

        // Create invite email
        $inviteEmail = new InviteEmail(
            email: $email,
            subject: 'Review and sign documents',
            message: 'Please review and sign the documents',
            expirationDays: 30
        );

        $inviteEmailCollection = new InviteEmailCollection([$inviteEmail]);

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

    private function downloadDocumentGroupFile(
        ApiClient $apiClient,
        string $documentGroupId
    ): string {
        $downloadRequest = (new DownloadDocumentGroupPost(
            'merged',
            'no'
        ))->withDocumentGroupId($documentGroupId);

        /** @var DownloadDocumentGroupPostResponse $response */
        $response = $apiClient->send($downloadRequest);

        $content = file_get_contents($response->getFile()->getRealPath());
        unlink($response->getFile()->getRealPath());

        return $content;
    }
} 