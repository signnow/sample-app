<?php

declare(strict_types=1);

namespace Samples\HROnboardingSystem;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\DocumentGroup\Request\Data\DocumentIdCollection;
use SignNow\Api\DocumentGroup\Request\DocumentGroupGet;
use SignNow\Api\DocumentGroup\Request\DocumentGroupPost;
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsGet;
use SignNow\Api\DocumentGroup\Request\DownloadDocumentGroupPost;
use SignNow\Api\DocumentGroup\Response\Data\Document\DocumentItem;
use SignNow\Api\DocumentGroupInvite\Request\GroupInvitePost;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteStep;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteStepCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteAction;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteActionCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteEmail;
use SignNow\Api\DocumentGroupInvite\Request\Data\InviteStep\InviteEmailCollection;
use SignNow\Api\DocumentGroupInvite\Request\Data\CcCollection;
use SignNow\Api\DocumentGroupInvite\Request\GroupInviteGet;
use SignNow\Api\DocumentGroupInvite\Response\GroupInvitePost as GroupInvitePostResponse;
use SignNow\Api\DocumentGroupInvite\Response\GroupInviteGet as GroupInviteGetResponse;
use SignNow\Api\Template\Request\CloneTemplatePost;
use SignNow\Api\Template\Response\CloneTemplatePost as CloneTemplatePostResponse;
use Symfony\Component\HttpFoundation\Response;
use SignNow\Api\DocumentField\Request\Data\Field as FieldValue;
use SignNow\Api\DocumentField\Request\Data\FieldCollection as FieldValueCollection;
use SignNow\Api\DocumentField\Request\DocumentPrefillPut;
use SignNow\ApiClient;
use SignNow\Sdk;
use SignNow\Api\DocumentGroup\Response\DocumentGroupGet as DocumentGroupGetResponse;
use SignNow\Api\DocumentGroup\Response\DocumentGroupPost as DocumentGroupPostResponse;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\DocumentGroup\Response\DownloadDocumentGroupPost as DownloadDocumentGroupPostResponse;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsGet as DocumentGroupRecipientsGetResponse;

class SampleController implements SampleControllerInterface
{
    /**
     * Handles GET request to render the main onboarding form page.
     * */
    public function handleGet(Request $request): Response
    {
        return new Response(
            view('HROnboardingSystem::index')->render(),
            200,
            [
                'Content-Type' => 'text/html',
            ]
        );
    }

    /**
     * Handles incoming POST requests and routes them based on the provided `action` parameter.
     *
     * Supported actions:
     *
     * 1. **create-invite**:
     *    - Triggered after the user submits the onboarding form and selects templates.
     *    - Calls `createDocumentGroup()` to:
     *        - Clone selected templates into live documents,
     *        - Prefill necessary fields (e.g., name, email),
     *        - Combine documents into a document group.
     *    - Then calls `sendInvite()` to create invites for HR Manager and Employee.
     *    - Returns the document group ID in a JSON response.
     *
     * 2. **invite-status**:
     *    - Periodically called from the frontend to poll the status of the document group invite.
     *    - Calls `getDocumentGroupSignersStatus()` to retrieve the current signing status.
     *    - Returns the status (e.g. pending, fulfilled) in a JSON response.
     *
     * 3. **default (no action or unrecognized action)**:
     *    - Assumes the request is to download the signed document group.
     *    - Calls `downloadDocumentGroup()` to retrieve the merged signed PDF.
     *    - Returns the document in an HTTP response with download headers.
     *
     * This method acts as the main backend entry point for frontend interactions
     * in the onboarding workflow — from document generation to signing and download.
     */
    public function handlePost(Request $request): Response
    {
        $action = $request->input('action');

        $sdk = new Sdk();
        $apiClient = $sdk->build()
            ->authenticate()
            ->getApiClient();

        switch ($action) {
            case 'create-invite':
                return $this->createInvite($request, $apiClient);
            case 'invite-status':
                return $this->getInviteStatus($request, $apiClient);
            case 'download-doc-group':
                return $this->downloadDocumentGroup($request, $apiClient);
            default:
                return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
        }
    }

    private function createInvite(Request $request, ApiClient $apiClient): JsonResponse
    {
        $employeeName = $request->input('employee_name');
        $employeeEmail = $request->input('employee_email');
        $hrManagerEmail = $request->input('hr_manager_email');
        $employerEmail = $request->input('employer_email');
        $template_ids = $request->input('template_ids');

        if (!$employeeName || !$employeeEmail || !$hrManagerEmail || !$employerEmail || !$template_ids) {
            return response()->json(['success' => false, 'message' => 'All fields are required'], 400);
        }

        // 1. Create Document Group from templates
        $documentGroupId = $this->createDocumentGroup(
            apiClient: $apiClient,
            template_ids: $template_ids,
            fields: [
                'Name' => $employeeName,
                'Text Field 2' => $employeeName,
                'Text Field 156' => $employeeName,
                'Email' => $employeeEmail,
            ]
        );

        // 2. Send invite to recipients
        $inviteResponse = $this->sendInvite(
            $apiClient,
            $documentGroupId,
            $employeeEmail,
            $hrManagerEmail,
            $employerEmail
        );

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
            'Content-Disposition' => 'attachment; filename="onboarding_documents.pdf"'
        ]);
    }

    /**
     * Creates a document group from selected templates with prefilled data.
     *
     * Steps performed:
     * 1. Clones each selected template into a signable document using `createDocumentFromTemplate()`.
     * 2. For each cloned document, fills in predefined fields (like name and email)
     *    using the provided data via `prefillFields()`.
     * 3. Combines all documents into a single document group using `createDocumentGroupFromDocuments()`.
     *
     * This function is called after the user submits the onboarding form
     * and selects which documents to include. The result is a ready-to-sign
     * document group that can be used to initiate the signing process.
     */
    private function createDocumentGroup(
        ApiClient $apiClient,
        array $template_ids,
        array $fields
    ): string {

        $documentsFromTemplate = [];
        foreach ($template_ids as $template_id) {
            $documentsFromTemplate[] = $this->createDocumentFromTemplate(
                $apiClient,
                $template_id,
            );
        }

        foreach ($documentsFromTemplate as $documentFromTemplate) {
            $this->prefillFields(
                $apiClient,
                $documentFromTemplate->getId(),
                $fields
            );
        }

        $documentGroupPostResponse = $this->createDocumentGroupFromDocuments(
            $apiClient,
            documentIds: array_map(
                fn(CloneTemplatePostResponse $document): string => $document->getId(),
                $documentsFromTemplate
            ),
            groupName: 'HR Onboarding System'
        );

        return $documentGroupPostResponse->getId();
    }

    /**
     * Sends invites to Contract Preparer, HR Manager and Employee for document signing.
     *
     * Steps performed:
     * 1. Retrieves document group metadata using `getDocumentGroup()`.
     * 2. Creates invite actions for each document in the group.
     * 3. Creates invite emails for all recipients with appropriate subjects and messages.
     * 4. Creates invite steps with proper ordering - Contract Preparer signs first, then HR Manager, then Employee.
     * 5. Sends the invite request using `GroupInvitePost`.
     *
     * This function is used immediately after the document group is created.
     * It sets up the actual signing flow, defining the signing order and
     * sending email notifications to all participants.
     */
    private function sendInvite(
        ApiClient $apiClient,
        string $documentGroupId,
        string $employeeEmail,
        string $hrManagerEmail,
        string $employerEmail
    ): array {
        $documentGroupGetResponse = $this->getDocumentGroup($apiClient, $documentGroupId);

        // Create invite actions for each document
        $inviteActions = [];
        $inviteEmails = [];

        // Define role mappings
        $roleMappings = [
            'Contract Preparer' => $hrManagerEmail,
            'Employee' => $employeeEmail,
            'Employer' => $employerEmail,
        ];

        foreach ($documentGroupGetResponse->getDocuments() as $document) {
            $documentRoles = $this->getDocumentRoles($apiClient, $document->getId());


            foreach ($roleMappings as $roleName => $email) {
                if (in_array($roleName, $documentRoles)){
                    $inviteActions[] = new InviteAction(
                        email: $email,
                        roleName: $roleName,
                        action: 'sign',
                        documentId: $document->getId(),
                        allowReassign: '0',
                        declineBySignature: '0',
                        redirectUri: config('app.url') . '/samples/HROnboardingSystem?page=status-page&document_group_id='
                        . $documentGroupId,
                        redirectTarget: 'self'
                    );
                } else {
                    // If role is not in mappings, assign as viewer
                    $inviteActions[] = new InviteAction(
                        email: $email,
                        roleName: $roleName,
                        action: 'view',
                        documentId: $document->getId(),
                    );
                }
            }
        }

        foreach ($roleMappings as $roleName => $email) {
            $inviteEmails[] = new InviteEmail(
                email: $email,
                subject: 'HR Onboarding Documents - Action Required',
                message: "Please review and sign the onboarding documents as {$roleName}.",
                expirationDays: 30
            );
        }

        $inviteActionCollection = new InviteActionCollection($inviteActions);

        // Create invite emails collection
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

    /**
     * Fetches metadata and details of a document group by ID.
     */
    private function getDocumentGroup(
        ApiClient $apiClient,
        string $documentGroupId
    ): DocumentGroupGetResponse {
        $documentGroupGetRequest = (new DocumentGroupGet())
            ->withDocumentGroupId($documentGroupId);

        return $apiClient->send($documentGroupGetRequest);
    }

    /**
     * Retrieves detailed information about a specific document by ID.
     */
    private function getDocument(
        ApiClient $apiClient,
        string $documentId
    ): DocumentGetResponse {
        $documentGet = (new DocumentGet())
            ->withDocumentId($documentId);

        return $apiClient->send($documentGet);
    }

    /**
     * Creates a document group from multiple documents.
     */
    private function createDocumentGroupFromDocuments(
        ApiClient $apiClient,
        array $documentIds,
        string $groupName,
    ): DocumentGroupPostResponse {
        $documentGroupPost = new DocumentGroupPost(
            documentIds: new DocumentIdCollection($documentIds),
            groupName: $groupName
        );

        return $apiClient->send($documentGroupPost);
    }

    /**
     * Create a document from a template by template ID.
     */
    private function createDocumentFromTemplate(
        ApiClient $apiClient,
        string $templateId,
    ): CloneTemplatePostResponse {
        $cloneTemplate = (new CloneTemplatePost())
            ->withTemplateId($templateId);

        /**@var CloneTemplatePostResponse $cloneTemplateResponse*/
        $cloneTemplateResponse = $apiClient->send($cloneTemplate);

        return $cloneTemplateResponse;
    }

    /**
     * Prefills specific fields in a given document with provided values.
     *
     * Steps performed:
     * 1. Retrieves the document metadata using `getDocument()`, which includes all fields
     *    currently defined in the document.
     *
     * 2. Extracts the names of all available fields in the document to ensure
     *    only valid fields will be prefilled.
     *
     * 3. Iterates over the provided `$fieldsValue` associative array
     *    (field name => value) and adds only:
     *    - Fields that actually exist in the document.
     *    - Fields that have a non-null value.
     *    These are collected into a `FieldValueCollection`.
     *
     * 4. If there are valid fields to fill, creates a `DocumentPrefillPut` request
     *    with the collected values and sends it to the SignNow API.
     *
     * This function ensures that only valid, defined fields in the document are updated,
     * avoiding potential errors from sending unknown or empty fields.
     *
     * It is typically used right after cloning a document from a template,
     * before showing it to the first signer
     */
    private function prefillFields(
        ApiClient $apiClient,
        string $documentId,
        array $fieldsValue
    ): void {
        $document = $this->getDocument($apiClient, $documentId);

        $exitedFields = [];
        foreach ($document->getFields() as $field) {
            $exitedFields[] = $field->getJsonAttributes()->getName();
        }

        $fields = new FieldValueCollection([]);

        foreach ($fieldsValue as $fieldName => $fieldValue) {
            if (!in_array($fieldName, $exitedFields)) {
                continue;
            }
            if ($fieldValue == null) {
                continue;
            }
            $fields->add(
                new FieldValue(
                    fieldName: $fieldName,
                    prefilledText: $fieldValue,
                )
            );
        }

        if (!$fields->isEmpty()) {
            $patchFields = new DocumentPrefillPut($fields);
            $patchFields->withDocumentId($documentId);
            $apiClient->send($patchFields);
        }
    }

    /**
     * Retrieves the current status of document group signers.
     */
    private function getDocumentGroupSignersStatus(
        ApiClient $apiClient,
        string $documentGroupId
    ): array {
        $documentGroupGetResponse = $this->getDocumentGroup($apiClient, $documentGroupId);

        $documentGroupInviteGet = (new GroupInviteGet())
            ->withDocumentGroupId($documentGroupId)
            ->withInviteId($documentGroupGetResponse->getInviteId());

        /**@var GroupInviteGetResponse $response */
        $response = $apiClient->send($documentGroupInviteGet);
        return ['status' => $response->getInvite()->getStatus()];
    }

    /**
     * Gets available roles from a document.
     */
    private function getDocumentRoles(
        ApiClient $apiClient,
        string $documentId
    ): array {
        $document = $this->getDocument($apiClient, $documentId);
        $roles = [];

        foreach ($document->getRoles() as $role) {
            /**@var Role $role*/
            $roleName = $role->getName();
            if ($roleName && !in_array($roleName, $roles)) {
                $roles[] = $roleName;
            }
        }

        return $roles;
    }

    /**
     * Downloads a document group as a single merged PDF file.
     *
     * Steps performed:
     * 1. Creates a `DownloadDocumentGroupPost` request with options:
     *    - `'merged'`: combines all documents in the group into one PDF file.
     *    - `'no'`: indicates that the documents should not include history/tracking pages.
     *
     * 2. Sends the request to the SignNow API and receives a response containing
     *    a temporary file path to the generated PDF on the server.
     *
     * 3. Reads the file content from the path using `file_get_contents()`.
     *
     * 4. Deletes the temporary file from the server to clean up.
     *
     * 5. Returns the raw PDF content as a string, which can then be sent back
     *    to the user in an HTTP response with appropriate headers.
     *
     * allowing users to download the fully signed and merged onboarding documents.
     */
    private function downloadDocumentGroupFile(
        ApiClient $apiClient,
        string $documentGroupId
    ): string {

        $downloadDocumentGroup = (new DownloadDocumentGroupPost(
            'merged',
            'no'
        ))->withDocumentGroupId($documentGroupId);
        $response = $apiClient->send($downloadDocumentGroup);
        /** @var DownloadDocumentGroupPostResponse $response */

        $content = file_get_contents($response->getFile()->getRealPath());

        unlink($response->getFile()->getRealPath());

        return $content;
    }
}
