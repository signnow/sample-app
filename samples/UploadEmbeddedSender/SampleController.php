<?php

declare(strict_types=1);

namespace Samples\UploadEmbeddedSender;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Request\DocumentPost;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\Document\Response\DocumentPost as DocumentPostResponse;
use SignNow\Api\DocumentGroup\Request\DocumentGroupGet;
use SignNow\Api\DocumentGroup\Request\DocumentGroupPost;
use SignNow\Api\DocumentGroup\Request\DownloadDocumentGroupPost;
use SignNow\Api\DocumentGroup\Request\Data\DocumentIdCollection;
use SignNow\Api\DocumentGroup\Response\DocumentGroupGet as DocumentGroupGetResponse;
use SignNow\Api\DocumentGroup\Response\DocumentGroupPost as DocumentGroupPostResponse;
use SignNow\Api\DocumentGroup\Response\DownloadDocumentGroupPost as DownloadDocumentGroupPostResponse;
use SignNow\Api\DocumentGroupInvite\Request\GroupInviteGet;
use SignNow\Api\DocumentGroupInvite\Response\GroupInviteGet as GroupInviteGetResponse;
use SignNow\Api\DocumentGroupInvite\Response\Data\Invite\Action;
use SignNow\Api\DocumentGroupInvite\Response\Data\Invite\Step;
use SignNow\Api\EmbeddedSending\Request\DocumentGroupEmbeddedSendingLinkPost;
use SignNow\Api\EmbeddedSending\Response\DocumentGroupEmbeddedSendingLinkPost as DocumentGroupEmbeddedSendingLinkPostResponse;
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsGet;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsGet as DocumentGroupRecipientsGetResponse;
use SignNow\ApiClient;
use SignNow\Sdk;
use Symfony\Component\HttpFoundation\Response;

class SampleController implements SampleControllerInterface
{
    public function handleGet(Request $request): Response
    {
        return new Response(
            view('UploadEmbeddedSender::index')->render(),
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
            case 'upload_and_create_dg':
                return $this->uploadAndCreateDocumentGroup($request, $apiClient);
            case 'invite-status':
                return $this->getInviteStatus($request, $apiClient);
            case 'download-doc-group':
                return $this->downloadDocumentGroup($request, $apiClient);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    private function uploadAndCreateDocumentGroup(Request $request, ApiClient $apiClient): JsonResponse
    {
        // 1. Upload PDF file to SignNow
        $documentResponse = $this->uploadDocument($apiClient);
        if (!$documentResponse['success']) {
            return response()->json($documentResponse, 500);
        }

        $documentId = $documentResponse['document_id'];

        // 2. Create Document Group from uploaded document
        $documentGroupResponse = $this->createDocumentGroup($apiClient, $documentId);
        if (!$documentGroupResponse['success']) {
            return response()->json($documentGroupResponse, 500);
        }

        $documentGroupId = $documentGroupResponse['document_group_id'];

        // 3. Create embedded sending link
        $embeddedSendingResponse = $this->createEmbeddedSendingUrl($apiClient, $documentGroupId);
        if (!$embeddedSendingResponse['success']) {
            return response()->json($embeddedSendingResponse, 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded and embedded sending link created successfully',
            'embedded_url' => $embeddedSendingResponse['embedded_url']
        ]);
    }

    private function uploadDocument(ApiClient $apiClient): array
    {
        // Read the PDF file from samples directory
        $pdfPath = __DIR__ . '/Sales Proposal.pdf';

        if (!file_exists($pdfPath)) {
            return ['success' => false, 'message' => 'PDF file not found'];
        }

        // Create SplFileInfo object for the PDF file
        $fileInfo = new \SplFileInfo($pdfPath);

        // Create document upload request
        $documentPost = new DocumentPost(
            file: $fileInfo,
            name: 'Sales Proposal'
        );

        /** @var DocumentPostResponse $response */
        $response = $apiClient->send($documentPost);

        return [
        'success' => true,
        'document_id' => $response->getId()
        ];
    }

    private function createDocumentGroup(ApiClient $apiClient, string $documentId): array
    {
        // Create document group with the uploaded document
        $documentIdCollection = new DocumentIdCollection([$documentId]);

        $documentGroupPost = new DocumentGroupPost(
            documentIds: $documentIdCollection,
            groupName: 'Uploaded Document Group'
        );

        /** @var DocumentGroupPostResponse $response */
        $response = $apiClient->send($documentGroupPost);

        return [
            'success' => true,
            'document_group_id' => $response->getId()
        ];
    }

    private function createEmbeddedSendingUrl(ApiClient $apiClient, string $documentGroupId): array
    {
        // Return URL after embedded sending to redirect to status page
        $redirectUrl = config('app.url')
            . '/samples/UploadEmbeddedSender?' .
            http_build_query([
                'page' => 'status-page',
                'document_group_id' => $documentGroupId,
            ]);

        $embeddedSendingRequest = new DocumentGroupEmbeddedSendingLinkPost(
            redirectUri: $redirectUrl,
            redirectTarget: 'self',
            linkExpiration: 15, // 15 minutes
            type: 'edit'
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

    private function getInviteStatus(Request $request, ApiClient $apiClient): JsonResponse
    {
        $documentGroupId = $request->input('document_group_id');
        $signers = $this->getDocumentGroupSignersStatus($apiClient, $documentGroupId);
        return response()->json($signers);
    }

    private function downloadDocumentGroup(Request $request, ApiClient $apiClient): Response
    {
        $documentGroupId = $request->input('document_group_id');
        $fileContents = $this->downloadDocumentGroupFile($apiClient, $documentGroupId);

        return new Response(
            $fileContents,
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="final_document_group.pdf"',
            ]
        );
    }

    private function getDocumentGroup(ApiClient $apiClient, string $documentGroupId): DocumentGroupGetResponse
    {
        $request = new DocumentGroupGet();
        $request->withDocumentGroupId($documentGroupId);
        return $apiClient->send($request);
    }

    /**
     * Get the list of recipients from the Document Group.
     */
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
    private function getDocumentGroupSignersStatus(ApiClient $apiClient, string $documentGroupId): array
    {
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
    private function downloadDocumentGroupFile(ApiClient $apiClient, string $documentGroupId): string
    {
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
