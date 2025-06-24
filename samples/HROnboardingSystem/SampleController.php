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
use SignNow\Api\DocumentGroupInvite\Request\GroupInviteGet;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\Document;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\DocumentCollection;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\Signer;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\SignerCollection;
use SignNow\Api\EmbeddedGroupInvite\Request\GroupInviteLinkPost;
use SignNow\Api\EmbeddedGroupInvite\Request\GroupInvitePost;
use SignNow\Api\Template\Request\CloneTemplatePost;
use SignNow\Api\Template\Response\CloneTemplatePost as CloneTemplatePostResponse;
use Symfony\Component\HttpFoundation\Response;
use SignNow\Api\DocumentField\Request\Data\Field as FieldValue;
use SignNow\Api\DocumentField\Request\Data\FieldCollection as FieldValueCollection;
use SignNow\Api\DocumentField\Request\DocumentPrefillPut;
use SignNow\ApiClient;
use SignNow\Sdk;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\Invite;
use SignNow\Api\EmbeddedGroupInvite\Response\GroupInvitePost as GroupInvitePostResponse;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\InviteCollection;
use SignNow\Api\DocumentGroup\Response\DocumentGroupGet as DocumentGroupGetResponse;
use SignNow\Api\DocumentGroup\Response\DocumentGroupPost as DocumentGroupPostResponse;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\EmbeddedGroupInvite\Response\GroupInviteLinkPost as GroupInviteLinkPostResponse;
use SignNow\Api\DocumentGroup\Response\DownloadDocumentGroupPost as DownloadDocumentGroupPostResponse;
use SignNow\Api\DocumentGroupInvite\Response\GroupInviteGet as GroupInviteGetResponse;

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
     * 1. **create-embedded-invite**:
     *    - Triggered after the user submits the onboarding form and selects templates.
     *    - Calls `createDocumentGroup()` to:
     *        - Clone selected templates into live documents,
     *        - Prefill necessary fields (e.g., name, email),
     *        - Combine documents into a document group.
     *    - Then calls `createEmbeddedInvite()` to create an embedded invite
     *      for two roles: HR Manager and Employee.
     *    - Finally, calls `getEmbeddedInviteLink()` to generate a link
     *      for the first recipient (Employee) to begin signing.
     *    - Returns the embedded invite link in a JSON response.
     *
     * 2. **invite-status**:
     *    - Periodically called from the frontend to poll the status of the document group invite.
     *    - Calls `getDocumentGroupInviteStatus()` to retrieve the current signing status.
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

        if ($action === 'create-embedded-invite') {
            $employeeName = $request->input('employee_name');
            $employeeEmail = $request->input('employee_email');
            $hrManagerEmail = $request->input('hr_manager_email');
            $template_ids = $request->input('template_ids');

            $contractPreparerEmail = config('signnow.api.signer_email');

            $DGId = $this->createDocumentGroup(
                apiClient: $apiClient,
                template_ids: $template_ids,
                fields: [
                    'Name' => $employeeName,
                    'Text Field 2' => $employeeName,
                    'Text Field 156' => $employeeName,
                    'Email' => $employeeEmail,
                ]
            );

            $embeddedInviteResponse = $this->createEmbeddedInvite(
                $apiClient,
                $DGId,
                $employeeEmail,
                $hrManagerEmail,
                $contractPreparerEmail,
            );

            $link = $this->getEmbeddedInviteLink(
                $apiClient,
                $DGId,
                $embeddedInviteResponse->getData()->getId(),
                $contractPreparerEmail
            );

            return new JsonResponse(['link' => $link]);
        } elseif ($action === "invite-status") {
            $documentGroupId = $request->input('document_group_id');

            $status = $this->getDocumentGroupInviteStatus(
                $apiClient,
                $documentGroupId
            );

            return new JsonResponse(['status' => $status]);
        } else {
            $documentGroupId = $request->input('document_group_id');

            $file = $this->downloadDocumentGroup($apiClient, $documentGroupId);

            return new Response($file, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="result.pdf"',
            ]);
        }
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
     * Creates an embedded invite for two recipients: Employee and HR Manager.
     *
     * Steps performed:
     * 1. Retrieves document group metadata and signer roles using `getDocumentGroup()`.
     * 2. Matches signer roles ('Employee', 'Employer') to the provided emails.
     * 3. Groups documents by signer and role, assigning each signer the relevant documents.
     * 4. Creates two ordered invites — HR Manager signs first, then Employee.
     * 5. Sends the invite request using `GroupInvitePost`, enabling embedded signing flow.
     *
     * This function is used immediately after the document group is created.
     * It sets up the actual signing flow, assigning specific documents to each signer
     * and defining the signing order.
     */
    private function createEmbeddedInvite(
        ApiClient $apiClient,
        string $documentGroupId,
        string $employeeEmail,
        string $hrManagerEmail,
        string $contractPreparerEmail,
    ): GroupInvitePostResponse {
        $documentGroupGetResponse = $this->getDocumentGroup($apiClient, $documentGroupId);

        $emailList = [
            'Contract Preparer' => $contractPreparerEmail,
            'Employee' => $employeeEmail,
            'Employer' => $hrManagerEmail,
        ];

        $signerDocs = [
            'Contract Preparer' => [],
            'Employee' => [],
            'Employer' => [],
        ];

        print_r($documentGroupGetResponse);

        foreach ($documentGroupGetResponse->getDocuments() as $document) {
            /**@var $document DocumentItem */
            foreach ($document->getRoles() as $role) {
                if ($role === 'Employee') {
                    $signerDocs['Employee'][$document->getId()] = new Document(
                        id: $document->getId(),
                        action: 'sign',
                        role: $role
                    );
                } elseif ($role === 'Employer') {
                    $signerDocs['Employer'][$document->getId()] = new Document(
                        id: $document->getId(),
                        action: 'sign',
                        role: $role
                    );
                } elseif ($role === 'Contract Preparer') {
                    $signerDocs['Contract Preparer'][$document->getId()] = new Document(
                        id: $document->getId(),
                        action: 'sign',
                        role: $role
                    );
                }
            }
        }

        foreach ($documentGroupGetResponse->getDocuments() as $document) {
            foreach ($signerDocs as $roleName => $docs) {
                if (!isset($docs[$document->getId()])) {
                    $signerDocs[$roleName][$document->getId()] = new Document(
                        id: $document->getId(),
                        action: 'view',
                        role: $roleName
                    );
                }
            }
        }

        $redirectUrl = config('app.url')
            . '/samples/HROnboardingSystem?page=download-with-status&document_group_id='
            . $documentGroupId;

        $inviteList = [];
        $order = 1;
        foreach ($emailList as $role => $email) {
            $invite = new Invite(
                order: $order,
                signers: new SignerCollection([
                    new Signer(
                        email: $email,
                        authMethod: 'none',
                        documents: new DocumentCollection($signerDocs[$role]),
                        redirectUri: $redirectUrl,
                        redirectTarget: 'self'
                    )
                ])
            );

            $inviteList[] = $invite;
            $order++;
        }

        print_r($inviteList);

        $embeddedInviteRequest = new GroupInvitePost(
            invites: new InviteCollection($inviteList),
            signAsMerged: true
        );
        $embeddedInviteRequest->withDocumentGroupId($documentGroupId);

        return $apiClient->send($embeddedInviteRequest);
    }

    /**
     * Generates an embedded signing link for a specific invite in a document group.
     */
    private function getEmbeddedInviteLink(
        ApiClient $apiClient,
        string $documentGroupId,
        string $inviteId,
        string $email
    ): string {
        $inviteLinkRequest = (new GroupInviteLinkPost($email, 'none', 15))
            ->withDocumentGroupId($documentGroupId)
            ->withEmbeddedInviteId($inviteId);

        /**@var GroupInviteLinkPostResponse $inviteLinkResponse*/
        $inviteLinkResponse = $apiClient->send($inviteLinkRequest);

        return $inviteLinkResponse->getData()->getLink();
    }

    /**
     * Retrieves the current status of a document group invite (e.g. pending, fulfilled).
     */
    private function getDocumentGroupInviteStatus(
        ApiClient $apiClient,
        string $documentGroupId
    ): string {
        $documentGroupGetResponse = $this->getDocumentGroup($apiClient, $documentGroupId);

        $documentGroupInviteGet = (new GroupInviteGet())
            ->withDocumentGroupId($documentGroupId)
            ->withInviteId($documentGroupGetResponse->getInviteId());

        /**@var GroupInviteGetResponse $response */
        $response = $apiClient->send($documentGroupInviteGet);
        return $response->getInvite()->getStatus();
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
    private function downloadDocumentGroup(
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
