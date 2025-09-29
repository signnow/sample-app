<?php

declare(strict_types=1);

namespace Samples\EmbeddedEditingAndSigningDG;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\DocumentGroupInvite\Response\Data\Invite\Action;
use SignNow\Api\DocumentGroupInvite\Response\Data\Invite\Step;
use SignNow\ApiClient;
use SignNow\Sdk;
use Symfony\Component\HttpFoundation\Response;
use SignNow\Api\Document\Request\DocumentGet;
use SignNow\Api\Document\Response\DocumentGet as DocumentGetResponse;
use SignNow\Api\DocumentField\Request\Data\Field as FieldValue;
use SignNow\Api\DocumentField\Request\Data\FieldCollection as FieldValueCollection;
use SignNow\Api\DocumentField\Request\DocumentPrefillPut;
use SignNow\Api\DocumentGroup\Request\DocumentGroupGet;
use SignNow\Api\DocumentGroup\Response\DocumentGroupGet as DocumentGroupGetResponse;
use SignNow\Api\DocumentGroupInvite\Response\GroupInviteGet as GroupInviteGetResponse;
use SignNow\Api\EmbeddedGroupInvite\Request\GroupInviteLinkPost;
use SignNow\Api\EmbeddedGroupInvite\Request\GroupInvitePost;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\DocumentCollection;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\Document;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\Invite;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\InviteCollection;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\Signer;
use SignNow\Api\EmbeddedGroupInvite\Request\Data\Invite\SignerCollection;
use SignNow\Api\EmbeddedGroupInvite\Response\GroupInviteLinkPost as GroupInviteLinkPostResponse;
use SignNow\Api\EmbeddedGroupInvite\Response\GroupInvitePost as GroupInvitePostResponse;
use SignNow\Api\EmbeddedEditor\Request\DocumentGroupEmbeddedEditorLinkPost;
use SignNow\Api\EmbeddedEditor\Response\DocumentGroupEmbeddedEditorLinkPost
    as DocumentGroupEmbeddedEditorLinkPostResponse;
use SignNow\Api\DocumentGroupInvite\Request\GroupInviteGet;
use SignNow\Api\DocumentGroup\Request\DownloadDocumentGroupPost;
use SignNow\Api\DocumentGroup\Response\DownloadDocumentGroupPost as DownloadDocumentGroupPostResponse;
use SignNow\Api\DocumentGroupTemplate\Request\DocumentGroupTemplatePost;
use SignNow\Api\DocumentGroupTemplate\Response\DocumentGroupTemplatePost as DocumentGroupTemplatePostResponse;
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsGet;
use SignNow\Api\DocumentGroup\Request\DocumentGroupRecipientsPut;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsGet as DocumentGroupRecipientsGetResponse;
use SignNow\Api\DocumentGroup\Response\DocumentGroupRecipientsPut as DocumentGroupRecipientsPutResponse;
use SignNow\Api\DocumentGroup\Request\Data\Recipient\Recipient;
use SignNow\Api\DocumentGroup\Request\Data\Recipient\RecipientCollection;
use SignNow\Api\DocumentGroup\Request\Data\Recipient\Document as RecipientDocument;
use SignNow\Api\DocumentGroup\Request\Data\Recipient\DocumentCollection as RecipientDocumentCollection;
use SignNow\Api\DocumentGroup\Request\Data\CcCollection;

/**
 * Sample controller for Embedded Editing & Signing with a Document Group Template.
 *
 * Demonstrates these steps (matching the 4-page flow described in the README):
 *  1. Collect signer info, create Document Group directly from a DGT, prefill fields,
 *     update recipients with emails, get edit link.
 *  2. Embedded sending link (user can review docs in "edit" mode).
 *  3. Create embedded invite for the specified role ("Contract Preparer") and redirect to sign.
 *  4. Show send status and allow final document download.
 */
class SampleController implements SampleControllerInterface
{
    /**
     * Document Group Template ID (DGT ID) for cloning a document group.
     */
    private const DOCUMENT_GROUP_TEMPLATE_ID = '0d7fb734e962418bad79d8fb80bbdaaf1f8e8cd9';
    /**
     * Role names used across the sample.
     */
    private const ROLE_CONTRACT_PREPARER = 'Contract Preparer';
    private const ROLE_RECIPIENT_1       = 'Recipient 1';
    private const ROLE_RECIPIENT_2       = 'Recipient 2';

    /**
     * Renders Page 1 (collect signer information) via GET.
     */
    public function handleGet(Request $request): Response
    {
        // Simple example: show a form with:
        //   Signer 1 Name, Signer 1 Email,
        //   Signer 2 Name, Signer 2 Email
        return new Response(
            view('EmbeddedEditingAndSigningDG::index')->render(),
            200,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * Main entry for POST requests. Handles different actions in the workflow.
     *
     * Actions (via 'action' parameter):
     *  1) "submit-signer-info" -> Creates Document Group from DGT, prefills fields,
     *     updates recipients with emails, returns embedded "Edit Link."
     *  2) "create-embedded-invite" -> Creates embedded invite for "Contract Preparer," returns the signing link.
     *  3) "invite-status" -> Polls for doc group invite status (pending, fulfilled, etc.).
     *  4) "download-doc-group" -> Returns merged signed documents in PDF.
     */
    public function handlePost(Request $request): Response
    {
        $action = $request->input('action') ?? '';

        $sdk = new Sdk();
        $apiClient = $sdk->build()
            ->authenticate()
            ->getApiClient();

        switch ($action) {
            /**
             * Page 1 form submit.
             * - Create Document Group directly from an existing Document Group Template (DGT)
             * - Prefill "Text Field 18" (Signer1Name), "Text Field 19" (Signer2Name)
             * - Get recipients from Document Group and update them with email addresses:
             *      - "Recipient 1" => signer1Email
             *      - "Recipient 2" => signer2Email
             * - Create an embedded "Edit Link" for final doc group review
             * - Return the link or redirect to Page 2
             */
            case 'submit-signer-info':
                $signer1Name  = $request->input('signer_1_name');
                $signer1Email = $request->input('signer_1_email');
                $signer2Name  = $request->input('signer_2_name');
                $signer2Email = $request->input('signer_2_email');

                // 1. Create the document group directly from an existing Document Group Template (DGT)
                $cloneResponse = $this->createDocumentGroupFromTemplate(
                    $apiClient,
                    self::DOCUMENT_GROUP_TEMPLATE_ID
                );
                $documentGroupId = $cloneResponse->getData()->getUniqueId();

                // 2. Prefill the fields in each doc of the group
                //    "Text Field 18" => Signer1Name, "Text Field 19" => Signer2Name
                $this->prefillDocGroupFields($apiClient, $documentGroupId, [
                    'Signer 1 Name' => $signer1Name,
                    'Signer 2 Name' => $signer2Name,
                ]);

                // 3. Get recipients from Document Group and update them with email addresses
                $this->updateDocumentGroupRecipients($apiClient, $documentGroupId, [
                    self::ROLE_RECIPIENT_1 => $signer1Email,
                    self::ROLE_RECIPIENT_2 => $signer2Email,
                ]);

                // 4. Create an "Edit Link" for embedded sending
                $editLink = $this->createEmbeddedEditLink($apiClient, $documentGroupId);

                // Return JSON with link, or redirect. For simplicity, returning JSON.
                // On the frontend, you'd direct the user to Page 2 to click this link.
                return new JsonResponse([
                    'document_group_id' => $documentGroupId,
                    'edit_link'         => $editLink,
                ]);



            /**
             * Page 3 action:
             * - Creates embedded invite for role "Contract Preparer" (example)
             * - Immediately returns or redirects to the signing link
             */
            case 'create-embedded-invite':
                $documentGroupId         = $request->input('document_group_id');
                $contractPreparerEmail   = config('signnow.api.signer_email');

                // Create an embedded invite for the "Contract Preparer"
                $inviteResponse = $this->createEmbeddedInvite(
                    $apiClient,
                    $documentGroupId,
                    $contractPreparerEmail
                );

                // Get the signing link directly from the response
                $signingLink = $inviteResponse->getData()->getLink();

                return new JsonResponse([
                    'document_group_id' => $documentGroupId,
                    'signing_link'      => $signingLink,
                ]);

            /**
             * Page 4 action: check the invite status and return signers list
             */
            case 'invite-status':
                $documentGroupId = $request->input('document_group_id');
                $signers = $this->getDocumentGroupSignersStatus($apiClient, $documentGroupId);
                return new JsonResponse($signers);

            /**
             * Also Page 4, or a separate endpoint: user can download the doc once signing is done.
             */
            case 'download-doc-group':
            default:
                $documentGroupId = $request->input('document_group_id');
                $fileContents = $this->downloadDocumentGroup($apiClient, $documentGroupId);

                return new Response(
                    $fileContents,
                    200,
                    [
                        'Content-Type'        => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="final_document_group.pdf"',
                    ]
                );
        }
    }

    /**
     * 1. Create a Document Group directly from an existing Document Group Template.
     *    This uses the new DocumentGroupTemplatePost class to create a document group
     *    from a Document Group Template in one API call.
     */
    private function createDocumentGroupFromTemplate(
        ApiClient $apiClient,
        string $templateId
    ): DocumentGroupTemplatePostResponse {
        // Create document group directly from Document Group Template
        $documentGroupTemplatePost = new DocumentGroupTemplatePost(
            groupName: 'Embedded Editing & Signing Group'
        );
        $documentGroupTemplatePost->withTemplateGroupId($templateId);

        /** @var DocumentGroupTemplatePostResponse $response */
        $response = $apiClient->send($documentGroupTemplatePost);
        return $response;
    }

    /**
     * 2. Prefill the fields across all documents in the newly cloned Document Group.
     *    Here we fill "Text Field 18" with Signer1Name, and "Text Field 19" with Signer2Name, if they exist.
     */
    private function prefillDocGroupFields(
        ApiClient $apiClient,
        string $documentGroupId,
        array $fieldsToFill
    ): void {
        // Fetch group info to get each doc ID
        $docGroup = $this->getDocumentGroup($apiClient, $documentGroupId);

        // For each document in the group, check if that doc has these fields. Then fill them if found.
        foreach ($docGroup->getDocuments() as $docItem) {
            $docId = $docItem->getId();
            $documentData = $this->getDocument($apiClient, $docId);

            $existingFields = [];
            foreach ($documentData->getFields() as $field) {
                $existingFields[] = $field->getJsonAttributes()->getName();
            }

            // Build a field collection with only valid fields
            $fieldValues = new FieldValueCollection([]);
            foreach ($fieldsToFill as $fieldName => $fieldValue) {
                if (!empty($fieldValue) && in_array($fieldName, $existingFields)) {
                    $fieldValues->add(new FieldValue(
                        fieldName: $fieldName,
                        prefilledText: $fieldValue
                    ));
                }
            }

            // If there are any fields to fill, send the request
            if (!$fieldValues->isEmpty()) {
                $prefillRequest = new DocumentPrefillPut($fieldValues);
                $prefillRequest->withDocumentId($docId);
                $apiClient->send($prefillRequest);
            }
        }
    }

    /**
     * 3. Get the list of recipients from the Document Group.
     *    This retrieves the current recipients and their roles from the Document Group.
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
     * 4. Update the recipients in the Document Group with email addresses.
     *    This assigns email addresses to the recipients based on their roles.
     */
    private function updateDocumentGroupRecipients(
        ApiClient $apiClient,
        string $documentGroupId,
        array $recipientEmails
    ): DocumentGroupRecipientsPutResponse {
        // Get current recipients
        $recipientsResponse = $this->getDocumentGroupRecipients($apiClient, $documentGroupId);
        $currentRecipients = $recipientsResponse->getData()->getRecipients();

        // Create updated recipients with email addresses
        $updatedRecipients = [];
        foreach ($currentRecipients as $recipient) {
            $recipientName = $recipient->getName();
            $email = $recipientEmails[$recipientName] ?? null;

            // Convert Response DocumentCollection to Request DocumentCollection
            $requestDocuments = [];
            foreach ($recipient->getDocuments() as $document) {
                $requestDocuments[] = new RecipientDocument(
                    id: $document->getId(),
                    role: $document->getRole(),
                    action: $document->getAction()
                );
            }
            $requestDocumentCollection = new RecipientDocumentCollection($requestDocuments);

            $updatedRecipients[] = new Recipient(
                name: $recipientName,
                email: $email,
                order: $recipient->getOrder(),
                documents: $requestDocumentCollection
            );
        }

        $recipientsCollection = new RecipientCollection($updatedRecipients);
        $ccCollection = new CcCollection([]); // Empty CC collection

        $updateRequest = new DocumentGroupRecipientsPut(
            recipients: $recipientsCollection,
            cc: $ccCollection
        );
        $updateRequest->withDocumentGroupId($documentGroupId);

        /** @var DocumentGroupRecipientsPutResponse $response */
        $response = $apiClient->send($updateRequest);
        return $response;
    }

    /**
     * 4. Create an embedded "Edit Link" for the cloned Document Group using Embedded Editor.
     *    This link allows the user to open the doc group in an iframe for editing and reordering.
     */
    private function createEmbeddedEditLink(
        ApiClient $apiClient,
        string $documentGroupId
    ): string {
        // Create embedded editor link for document group
        $redirectUrl = config('app.url') .
            '/samples/EmbeddedEditingAndSigningDG?' . http_build_query([
                'page' => 'page2-embedded-sending',
                'document_group_id' => $documentGroupId,
            ]);

        $editLinkReq = (new DocumentGroupEmbeddedEditorLinkPost(
            redirectUri: $redirectUrl,
            redirectTarget: 'self',
            linkExpiration: 15
        ))->withDocumentGroupId($documentGroupId);

        /** @var DocumentGroupEmbeddedEditorLinkPostResponse $response */
        $response = $apiClient->send($editLinkReq);

        return $response->getData()->getUrl();
    }

    /**
     * 5. Creates an embedded invite for a specific role (e.g., "Contract Preparer") in the doc group.
     *    Then that role can immediately sign in an embedded session.
     */
    private function createEmbeddedInvite(
        ApiClient $apiClient,
        string $documentGroupId,
        string $contractPreparerEmail
    ): GroupInviteLinkPostResponse {
        // 1. Retrieve docs and roles from the group
        $docGroup = $this->getDocumentGroup($apiClient, $documentGroupId);

        $documentGroupRecipients = $this->getDocumentGroupRecipients($apiClient, $documentGroupId);

        $emailList = [
            self::ROLE_CONTRACT_PREPARER => $contractPreparerEmail,
            self::ROLE_RECIPIENT_1       => $this->findEmailByRoleName($documentGroupRecipients, self::ROLE_RECIPIENT_1),
            self::ROLE_RECIPIENT_2       => $this->findEmailByRoleName($documentGroupRecipients, self::ROLE_RECIPIENT_2),
        ];

        $signerDocs = [
            self::ROLE_CONTRACT_PREPARER => [],
            self::ROLE_RECIPIENT_1       => [],
            self::ROLE_RECIPIENT_2       => [],
        ];

        foreach ($docGroup->getDocuments() as $document) {
            /**@var $document DocumentItem */
            foreach ($document->getRoles() as $role) {
                if (is_string($role)) {
                    $signerDocs[$role][$document->getId()] = new Document(
                        id: $document->getId(),
                        action: 'sign',
                        role: $role
                    );
                }
            }
        }

        foreach ($docGroup->getDocuments() as $document) {
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

        // Return URL after signing
        $redirectUrl = config('app.url')
            . '/samples/EmbeddedEditingAndSigningDG?' .
            http_build_query([
                'page' => 'page4-status-download',
                'document_group_id' => $documentGroupId,
            ]);

        $inviteList = [];
        $order = 1;
        foreach ($emailList as $role => $email) {
            if ($role === self::ROLE_CONTRACT_PREPARER) {
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
            } else {
                $invite = new Invite(
                    order: $order,
                    signers: new SignerCollection([
                        new Signer(
                            email: $email,
                            authMethod: 'none',
                            documents: new DocumentCollection($signerDocs[$role]),
                            redirectUri: $redirectUrl,
                            redirectTarget: 'self',
                            deliveryType: 'email' // Use email delivery for other roles
                        )
                    ])
                );
            }

            $inviteList[] = $invite;
            $order++;
        }

        $embeddedInviteRequest = new GroupInvitePost(
            invites: new InviteCollection($inviteList),
            signAsMerged: true
        );
        $embeddedInviteRequest->withDocumentGroupId($documentGroupId);

        /** @var GroupInvitePostResponse $embeddedInviteResponse */
        $embeddedInviteResponse = $apiClient->send($embeddedInviteRequest);

        // Now get embedded link for this invite
        $embeddedLinkRequest = (new GroupInviteLinkPost($contractPreparerEmail, 'none', 30))
            ->withDocumentGroupId($documentGroupId)
            ->withEmbeddedInviteId($embeddedInviteResponse->getData()->getId());


        /** @var GroupInviteLinkPostResponse $embeddedLinkResponse */
        $embeddedLinkResponse = $apiClient->send($embeddedLinkRequest);

        // Return the embedded link response
        return $embeddedLinkResponse;
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
    private function downloadDocumentGroup(
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

    /**
     * Fetch Document Group info by ID.
     */
    private function getDocumentGroup(
        ApiClient $apiClient,
        string $documentGroupId
    ): DocumentGroupGetResponse {
        $req = (new DocumentGroupGet())->withDocumentGroupId($documentGroupId);
        /** @var DocumentGroupGetResponse $resp */
        $resp = $apiClient->send($req);
        return $resp;
    }

    /**
     * Fetch Document info by ID.
     */
    private function getDocument(
        ApiClient $apiClient,
        string $documentId
    ): DocumentGetResponse {
        $req = (new DocumentGet())->withDocumentId($documentId);
        return $apiClient->send($req);
    }

    private function findEmailByRoleName(
        DocumentGroupRecipientsGetResponse $recipientsResponse,
        string $roleName
    ): ?string {
        foreach ($recipientsResponse->getData()->getRecipients() as $recipient) {
            if ($recipient->getName() === $roleName) {
                return $recipient->getEmail();
            }
        }
        return null;
    }
}
