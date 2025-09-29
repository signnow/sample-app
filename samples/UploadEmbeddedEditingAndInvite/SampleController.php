<?php

declare(strict_types=1);

namespace Samples\UploadEmbeddedEditingAndInvite;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Request\DocumentPost;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\Document\Response\DocumentPost as DocumentPostResponse;
use SignNow\Api\Document\Request\DocumentDownloadGet;
use SignNow\Api\Document\Response\DocumentDownloadGet as DocumentDownloadGetResponse;
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
use SignNow\Api\DocumentGroupInvite\Request\GroupInvitePost;
use SignNow\Api\DocumentInvite\Request\Data\To;
use SignNow\Api\DocumentInvite\Request\Data\ToCollection;
use SignNow\Api\DocumentInvite\Request\SendInvitePost;
use SignNow\Api\DocumentInvite\Response\SendInvitePost as SendInvitePostResponse;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteStep;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteStepCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteAction;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteActionCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteEmail;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteEmailCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\CcCollection;
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsGet;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsGet as DocumentGroupRecipientsGetResponse;
use SignNow\Api\EmbeddedEditor\Request\DocumentEmbeddedEditorLinkPost;
use SignNow\Api\EmbeddedEditor\Response\DocumentEmbeddedEditorLinkPost as DocumentEmbeddedEditorLinkPostResponse;
use SignNow\ApiClient;
use SignNow\Sdk;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class SampleController implements SampleControllerInterface
{
    public function handleGet(Request $request): Response
    {
        return new Response(
            view('UploadEmbeddedEditingAndInvite::index')->render(),
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
            case 'create_embedded_edit':
                return $this->createEmbeddedEditLink($request, $apiClient);
            case 'create_invite':
                return $this->createInvite($request, $apiClient);
            case 'invite-status':
                return $this->getInviteStatus($request, $apiClient);
            case 'download-document':
                return $this->downloadDocument($request, $apiClient);
            case 'get-recipients':
                return $this->getDocumentRecipientsApi($request, $apiClient);
            case 'add-recipient':
                return $this->addDocumentRecipient($request, $apiClient);
            case 'get-document-roles':
                return $this->getDocumentRolesApi($request, $apiClient);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    private function getDocumentRecipientsApi(Request $request, ApiClient $apiClient): JsonResponse
    {
        $documentId = $request->input('document_id');

        if (!$documentId) {
            return response()->json(['success' => false, 'message' => 'Document ID is required'], 400);
        }

        $recipients = $this->getDocumentRecipients($apiClient, $documentId);

        return response()->json([
            'success' => true,
            'recipients' => $recipients
        ]);
    }

    private function getDocumentRolesApi(Request $request, ApiClient $apiClient): JsonResponse
    {
        $documentId = $request->input('document_id');

        if (!$documentId) {
            return response()->json(['success' => false, 'message' => 'Document ID is required'], 400);
        }

        // Get document info
        $documentGetResponse = $this->getDocument($apiClient, $documentId);
        $roles = $documentGetResponse->getRoles();

        $rolesData = [];
        foreach ($roles as $role) {
            $rolesData[] = [
                'name' => $role->getName(),
                'unique_id' => $role->getUniqueId(),
                'signing_order' => $role->getSigningOrder()
            ];
        }

        return response()->json([
            'success' => true,
            'roles' => $rolesData
        ]);
    }

    private function addDocumentRecipient(Request $request, ApiClient $apiClient): JsonResponse
    {
        $documentId = $request->input('document_id');
        $recipientName = $request->input('recipient_name');
        $recipientEmail = $request->input('recipient_email');
        $recipientRole = $request->input('recipient_role');

        if (!$documentId || !$recipientName || !$recipientEmail || !$recipientRole) {
            return response()->json(['success' => false, 'message' => 'All fields are required'], 400);
        }

        // Get document info
        $documentGetResponse = $this->getDocument($apiClient, $documentId);
        $roles = $documentGetResponse->getRoles();

        // Find existing role or create a new one
        $targetRole = null;
        foreach ($roles as $role) {
            if ($role->getName() === $recipientRole) {
                $targetRole = $role;
                break;
            }
        }

        if (!$targetRole) {
            return response()->json([
                'success' => false, 
                'message' => "Role '{$recipientRole}' not found in document. Available roles: " . 
                    implode(', ', array_map(fn($r) => $r->getName(), $roles->toArray()))
            ], 400);
        }

        // Create invite for the recipient
        $to = new ToCollection();
        $to->add(
            new To(
                $recipientEmail,
                $targetRole->getUniqueId(),
                $targetRole->getName(),
                (int) $targetRole->getSigningOrder(),
                'Document Signing Request - Action Required',
                "Dear {$recipientName}, please review and sign the uploaded document.",
                redirectUri: config('app.url') . '/samples/UploadEmbeddedEditingAndInvite?page=status-page&document_id=' . $documentId
            )
        );

        // Create and send invite request for document
        $inviteRequest = new SendInvitePost(
            $documentId,
            $to,
            'sender@signnow.com', // You can use your own sender email
            'Document Signing Request - Action Required',
            "Dear {$recipientName}, please review and sign the uploaded document."
        );
        $inviteRequest->withDocumentId($documentId);

        $apiClient->send($inviteRequest);

        return response()->json([
            'success' => true,
            'message' => 'Recipient added and invite sent successfully'
        ]);
    }

    private function uploadAndCreateDocumentGroup(Request $request, ApiClient $apiClient): JsonResponse
    {
        // Validate uploaded file
        if (!$request->hasFile('document_file')) {
            return response()->json(['success' => false, 'message' => 'No file uploaded'], 400);
        }

        $file = $request->file('document_file');
        $documentName = $file->getClientOriginalName(); // Use original filename

        // Validate file type
        if ($file->getClientOriginalExtension() !== 'pdf') {
            return response()->json(['success' => false, 'message' => 'Only PDF files are allowed'], 400);
        }

        // Additional validation - check MIME type
        if ($file->getMimeType() !== 'application/pdf') {
            return response()->json(['success' => false, 'message' => 'Invalid PDF file format'], 400);
        }

        // Check file size (max 50MB)
        if ($file->getSize() > 50 * 1024 * 1024) {
            return response()->json(['success' => false, 'message' => 'File size too large. Maximum 50MB allowed'], 400);
        }

        // 1. Upload PDF file to SignNow
        $documentResponse = $this->uploadDocument($apiClient, $file, $documentName);
        $documentId = $documentResponse['document_id'];

        return response()->json([
            'success' => true,
            'message' => 'Document uploaded successfully',
            'document_id' => $documentId
        ]);
    }

    private function createEmbeddedEditLink(Request $request, ApiClient $apiClient): JsonResponse
    {
        $documentId = $request->input('document_id');

        if (!$documentId) {
            return response()->json(['success' => false, 'message' => 'Document ID is required'], 400);
        }

        // Create embedded editor link for document
        $redirectUrl = config('app.url') .
            '/samples/UploadEmbeddedEditingAndInvite?' . http_build_query([
                'page' => 'invite-page',
                'document_id' => $documentId,
            ]);

        $editLinkReq = (new DocumentEmbeddedEditorLinkPost(
            redirectUri: $redirectUrl,
            redirectTarget: 'self',
            linkExpiration: 15
        ))->withDocumentId($documentId);

        /** @var DocumentEmbeddedEditorLinkPostResponse $response */
        $response = $apiClient->send($editLinkReq);

        return response()->json([
            'success' => true,
            'edit_link' => $response->getData()->getUrl()
        ]);
    }

    private function createInvite(Request $request, ApiClient $apiClient): JsonResponse
    {
        $documentId = $request->input('document_id');
        $signerEmail = $request->input('signer_email');
        $signerName = $request->input('signer_name');

        if (!$documentId || !$signerEmail || !$signerName) {
            return response()->json(['success' => false, 'message' => 'Document ID, signer email and name are required'], 400);
        }

        // Get document info and recipients
        $documentGetResponse = $this->getDocument($apiClient, $documentId);
        $recipients = $this->getDocumentRecipients($apiClient, $documentId);

        // Create invite for the document using real recipients
        $roles = $documentGetResponse->getRoles();
        $to = new ToCollection();
        
        foreach ($roles as $role) {
            // Find recipient for this role
            $recipient = null;
            foreach ($recipients as $rec) {
                if ($rec['role_id'] === $role->getUniqueId()) {
                    $recipient = $rec;
                    break;
                }
            }
            
            // Use recipient email or fallback to provided email
            $emailToUse = $recipient ? $recipient['email'] : $signerEmail;
            $nameToUse = $recipient ? $recipient['role'] : $signerName;
            
            $to->add(
                new To(
                    $emailToUse,
                    $role->getUniqueId(),
                    $role->getName(),
                    (int) $role->getSigningOrder(),
                    'Document Signing Request - Action Required',
                    "Dear {$nameToUse}, please review and sign the uploaded document.",
                    redirectUri: config('app.url') . '/samples/UploadEmbeddedEditingAndInvite?page=status-page&document_id=' . $documentId
                )
            );
        }

        // Create and send invite request for document
        $inviteRequest = new SendInvitePost(
            $documentId,
            $to,
            'sender@signnow.com', // You can use your own sender email
            'Document Signing Request - Action Required',
            "Dear {$signerName}, please review and sign the uploaded document."
        );
        $inviteRequest->withDocumentId($documentId);

        $apiClient->send($inviteRequest);

        return response()->json([
            'success' => true,
            'message' => 'Invite sent successfully'
        ]);
    }

    private function getInviteStatus(Request $request, ApiClient $apiClient): JsonResponse
    {
        $documentId = $request->input('document_id');
        $signers = $this->getDocumentSignersStatus($apiClient, $documentId);
        return response()->json($signers);
    }

    private function downloadDocument(Request $request, ApiClient $apiClient): Response
    {
        $documentId = $request->input('document_id');
        $fileContents = $this->downloadDocumentFile($apiClient, $documentId);

        return new Response(
            $fileContents,
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="final_document.pdf"',
            ]
        );
    }

    private function uploadDocument(ApiClient $apiClient, $uploadedFile, string $documentName): array
    {
        // Get the temporary file path
        $tempPath = $uploadedFile->getRealPath();
        
        if (!$tempPath || !file_exists($tempPath)) {
            return [
                'success' => false,
                'message' => 'Uploaded file not found or invalid'
            ];
        }

        // Debug: Log file information
        Log::alert('File upload debug', [
            'temp_path' => $tempPath,
            'file_exists' => file_exists($tempPath),
            'file_size' => filesize($tempPath),
            'mime_type' => mime_content_type($tempPath),
            'original_name' => $uploadedFile->getClientOriginalName(),
            'original_extension' => $uploadedFile->getClientOriginalExtension()
        ]);

        // Try alternative approach - create temporary file with proper extension
        $tempFileWithExtension = $tempPath . '.pdf';
        copy($tempPath, $tempFileWithExtension);
        
        // Create SplFileInfo object for the file with proper extension
        $fileInfo = new \SplFileInfo($tempFileWithExtension);

        // Create document upload request
        $documentPost = new DocumentPost(
            file: $fileInfo,
            name: $documentName
        );

        /** @var DocumentPostResponse $response */
        $response = $apiClient->send($documentPost);

        // Clean up temporary file
        if (file_exists($tempFileWithExtension)) {
            unlink($tempFileWithExtension);
        }

        return [
            'success' => true,
            'document_id' => $response->getId()
        ];
    }

    private function createDocumentGroup(ApiClient $apiClient, string $documentId): string
    {
        // Create document group with the uploaded document
        $documentIdCollection = new DocumentIdCollection([$documentId]);

        $documentGroupPost = new DocumentGroupPost(
            documentIds: $documentIdCollection,
            groupName: 'Uploaded Document Group'
        );

        /** @var DocumentGroupPostResponse $response */
        $response = $apiClient->send($documentGroupPost);

        return $response->getId();
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

    private function getDocumentRoles(ApiClient $apiClient, string $documentId): array
    {
        $document = $this->getDocument($apiClient, $documentId);
        $roles = [];

        foreach ($document->getRoles() as $role) {
            $roleName = $role->getName();
            if ($roleName && !in_array($roleName, $roles)) {
                $roles[] = $roleName;
            }
        }

        return $roles;
    }

    private function getDocumentRecipients(ApiClient $apiClient, string $documentId): array
    {

        // Get document info
        $documentGetResponse = $this->getDocument($apiClient, $documentId);
        $recipients = [];
        
        // Get routing details (recipients)
        $routingDetails = $documentGetResponse->getRoutingDetails();
        foreach ($routingDetails as $routingDetail) {
            $dataCollection = $routingDetail->getData();
            foreach ($dataCollection as $data) {
                $recipients[] = [
                    'email' => $data->getDefaultEmail(),
                    'role' => $data->getName(),
                    'role_id' => $data->getRoleId(),
                    'signing_order' => $data->getSigningOrder(),
                    'inviter_role' => $data->isInviterRole()
                ];
            }
        }
        
        return $recipients;
    }

    private function getDocumentSignersStatus(ApiClient $apiClient, string $documentId): array
    {
        $request = (new DocumentGet())->withDocumentId($documentId);

        /** @var DocumentGetResponse $response */
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

    private function downloadDocumentFile(ApiClient $apiClient, string $documentId): string
    {
        // For single document download, we'll use DocumentDownloadGet
        $downloadRequest = new DocumentDownloadGet();
        $downloadRequest->withDocumentId($documentId)
            ->withType('collapsed')
            ->withHistory('no');

        /** @var DocumentDownloadGetResponse $response */
        $response = $apiClient->send($downloadRequest);

        $content = file_get_contents($response->getFile()->getRealPath());
        unlink($response->getFile()->getRealPath());

        return $content;
    }
}
