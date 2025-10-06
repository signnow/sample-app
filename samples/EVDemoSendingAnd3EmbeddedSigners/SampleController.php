<?php

declare(strict_types=1);

namespace Samples\EVDemoSendingAnd3EmbeddedSigners;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SignNow\Api\Document\Response\Data\Role;
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
use SignNow\Api\EmbeddedInvite\Request\DocumentInvitePost as DocumentInvitePostRequest;
use SignNow\Api\EmbeddedInvite\Request\Data\Invite;
use SignNow\Api\EmbeddedInvite\Request\Data\InviteCollection;
use SignNow\Api\EmbeddedInvite\Request\DocumentInviteLinkPost;
use SignNow\Api\EmbeddedInvite\Response\DocumentInvitePost as DocumentInvitePostResponse;
use SignNow\Api\EmbeddedInvite\Response\DocumentInviteLinkPost as DocumentInviteLinkPostResponse;
use SplFileInfo;

/**
 * SampleController for the EVDemoSendingAnd3EmbeddedSigners application.
 *
 * Demonstrates a headless sending workflow from a single template (with roles:
 * "Contract Preparer," "Recipient 1," "Recipient 2") followed by three sequential
 * embedded signing sessions. After all signers finish, the final PDF is
 * downloadable from SignNow.
 */
class SampleController implements SampleControllerInterface
{
    private const TEMPLATE_ID = '34009a3d21b5468d86d886cd715658c453335c61';
    private const TARGET_SAMPLE = 'samples/EVDemoSendingAnd3EmbeddedSigners';

    /**
     * Handle GET requests for the EVDemoSendingAnd3EmbeddedSigners demo.
     *
     * Displays the initial form where user inputs Agent, Signer1, Signer2 details.
     */
    public function handleGet(Request $request): Response
    {
        if ($request->boolean('redirect')) {
            $queryParams = $request->query->all();
            unset($queryParams['redirect']);

            $targetUrl = $this->buildSampleUrl($queryParams);
            $encodedUrl = json_encode($targetUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encodedUrl === false) {
                $encodedUrl = '"' . addslashes($targetUrl) . '"';
            }

            $html = sprintf(
                <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Redirecting…</title>
</head>
<body>
<script>
    (function () {
        var target = %s;
        if (window.top && window.top !== window) {
            window.top.location.href = target;
        } else {
            window.location.href = target;
        }
    })();
</script>
</body>
</html>
HTML,
                $encodedUrl
            );

            return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
        }

        // Renders the Blade template `EVDemoSendingAnd3EmbeddedSigners::index`.
        // This view should include a form with fields:
        //  - Agent Name
        //  - Agent Email
        //  - Signer 1 Name
        //  - Signer 1 Email
        //  - Signer 2 Name
        //  - Signer 2 Email
        // and a button to POST to this controller with action="start-workflow".
        return new Response(
            view('EVDemoSendingAnd3EmbeddedSigners::index')->render(),
            200,
            ['Content-Type' => 'text/html']
        );
    }

    /**
     * Handle POST requests corresponding to:
     * 1) "start-workflow": Clones the template, assigns roles/emails,
     *    pre-fills name fields, and returns an embedded signing link for the Agent.
     * 2) "next-signer": Returns the embedded signing link for Signer 1 or Signer 2,
     *    depending on the "roleName" parameter in the request.
     * 3) "download": Retrieves the final signed PDF by document_id.
     * 4) "invite-status": Returns real-time status of each role's invite.
     */
    public function handlePost(Request $request): Response
    {
        $action = $request->input('action');

        $sdk = new Sdk();
        $apiClient = $sdk->build()->authenticate()->getApiClient();

        if ($action === 'start-workflow') {
            // Capture data from the submitted form
            $agentName = $request->input('agent_name');
            $agentEmail = $request->input('agent_email');
            $signer1Name = $request->input('signer1_name');
            $signer1Email = $request->input('signer1_email');
            $signer2Name = $request->input('signer2_name');
            $signer2Email = $request->input('signer2_email');

            // 1) Create a new document from the template
            $documentResponse = $this->createDocumentFromTemplate($apiClient, self::TEMPLATE_ID);
            $documentId = $documentResponse->getId();

            // 2) Pre-fill name fields in the document
            // Assuming the doc has text fields named "Agent Name", "Signer 1 Name", "Signer 2 Name"
            // Adjust accordingly if the actual field names differ
            $fieldsToPrefill = [
//                'Agent Name'   => $agentName,
                'Signer 1 Name' => $signer1Name,
                'Text Field 18' => $signer1Name,
                'Signer 2 Name' => $signer2Name,
                'Text Field 19' => $signer2Name,
            ];
            $this->prefillFields($apiClient, $documentId, $fieldsToPrefill);

            // 3) Create embedded invites for all three roles (Agent → Signer1 → Signer2)
            $agentRoleId   = $this->getRoleIdByName($apiClient, $documentId, 'Contract Preparer');
            $signer1RoleId = $this->getRoleIdByName($apiClient, $documentId, 'Recipient 1');
            $signer2RoleId = $this->getRoleIdByName($apiClient, $documentId, 'Recipient 2');

            $inviteMap = $this->createEmbeddedInvitesForAllSigners(
                $apiClient,
                $documentId,
                [
                    ['email' => $agentEmail,   'roleId' => $agentRoleId,   'order' => 1, 'name' => $agentName],
                    ['email' => $signer1Email, 'roleId' => $signer1RoleId, 'order' => 2, 'name' => $signer1Name],
                    ['email' => $signer2Email, 'roleId' => $signer2RoleId, 'order' => 3, 'name' => $signer2Name],
                ]
            );

            // 4) Generate embedded link only for the first role (Agent)
            $agentInviteId = $inviteMap[$agentRoleId] ?? null;

            $agentLink = $this->getEmbeddedInviteLink(
                $apiClient,
                $documentId,
                $agentInviteId,
                $this->makeRedirectUrl($documentId, 'signer1')
            );

            // Return the doc_id and the link for the Agent to sign first
            return new JsonResponse([
                'document_id' => $documentId,
                'embedded_link' => $agentLink,
                'message' => 'Agent embedded signing link created. Agent can now sign.'
            ]);
        }

        if ($action === 'next-signer') {
            // In the request, we expect "document_id" and "roleName" = either "Recipient 1" or "Recipient 2".
            $documentId = $request->input('document_id');
            $roleName = $request->input('roleName');

            // Determine which step comes next after this signer completes.
            $redirectKey = ($roleName === 'Recipient 1') ? 'signer2' : 'finish';

            $inviteId = $this->getInviteIdForRoleName($apiClient, $documentId, $roleName);

            $signingLink = $this->getEmbeddedInviteLink(
                $apiClient,
                $documentId,
                $inviteId,
                $this->makeRedirectUrl($documentId, $redirectKey)
            );

            return new JsonResponse([
                'embedded_link' => $signingLink,
                'message' => "Embedded link for {$roleName} created. Ready for signing."
            ]);
        }

        if ($action === 'download') {
            // Retrieve the final signed PDF
            $documentId = $request->input('document_id');
            $file = $this->downloadDocument($apiClient, $documentId);
            $content = file_get_contents($file->getRealPath());
            unlink($file->getRealPath());

            return new Response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $file->getFilename() . '"',
            ]);
        }

        if ($action === 'invite-status') {
            // Return the real-time invite statuses
            $documentId = $request->input('document_id');
            $statusList = $this->getDocumentStatuses($apiClient, $documentId);
            return new JsonResponse($statusList);
        }

        // Default fallback
        return new JsonResponse(['error' => 'Invalid action'], 400);
    }

    /**
     * 1) Clone a new document instance from the specified template.
     */
    private function createDocumentFromTemplate(ApiClient $apiClient, string $templateId): CloneTemplatePostResponse
    {
        $clone = new CloneTemplatePost();
        $clone->withTemplateId($templateId);
        return $apiClient->send($clone);
    }

    /**
     * 2) Pre-fill name fields in the document
     */
    private function prefillFields(ApiClient $apiClient, string $documentId, array $fieldsValue): void
    {
        $collection = new FieldValueCollection();

        foreach ($fieldsValue as $fieldName => $value) {
            if ($value !== null) {
                $collection->add(new FieldValue(fieldName: $fieldName, prefilledText: $value));
            }
        }

        $request = new DocumentPrefillPut($collection);
        $request->withDocumentId($documentId);
        $apiClient->send($request);
    }

    /**
     * 3) Create embedded invites for all three roles (Agent → Signer1 → Signer2)
     */
    private function createEmbeddedInvitesForAllSigners(
        ApiClient $apiClient,
        string $documentId,
        array $signers
    ): array {
        $inviteObjects = [];
        foreach ($signers as $signer) {
            $parts = preg_split('/\s+/', trim($signer['name'] ?? ''));
            $firstName = $parts[0] ?? '';
            $lastName  = $parts[1] ?? $firstName;

            $inviteObjects[] = new Invite(
                email: $signer['email'],
                roleId: $signer['roleId'],
                order: $signer['order'],
                authMethod: 'none',
                firstName: $firstName,
                lastName: $lastName,
            );
        }

        $documentInvite = new DocumentInvitePostRequest(
            invites: new InviteCollection($inviteObjects)
        );

        /** @var DocumentInvitePostResponse $response */
        $response = $apiClient->send($documentInvite->withDocumentId($documentId));

        // Build map roleId => inviteId (field_invite_id)
        $inviteMap = [];
        foreach ($response->getData() as $inviteData) {
            $inviteMap[$inviteData->getRoleId()] = $inviteData->getId();
        }

        return $inviteMap;
    }

    /**
     * Builds the embedded signing link for a given invite ID and appends a redirect URL.
     */
    private function getEmbeddedInviteLink(
        ApiClient $apiClient,
        string $documentId,
        string $inviteId,
        string $redirectUrl
    ): string {
        $inviteLinkRequest = new DocumentInviteLinkPost('none', 15);
        $inviteLinkRequest->withFieldInviteId($inviteId);
        $inviteLinkRequest->withDocumentId($documentId);

        /** @var DocumentInviteLinkPostResponse $response */
        $response = $apiClient->send($inviteLinkRequest);

        return $response->getData()->getLink() . '&redirect_uri=' . urlencode($redirectUrl);
    }

    /**
     * Returns the invite ID associated with a particular role name.
     * Assumes the invites were already created earlier in the flow (start-workflow).
     */
    private function getInviteIdForRoleName(
        ApiClient $apiClient,
        string $documentId,
        string $roleName
    ): string {
        $roleId = $this->getRoleIdByName($apiClient, $documentId, $roleName);

        /** @var DocumentGetResponse $document */
        $document = $apiClient->send((new DocumentGet())->withDocumentId($documentId));

        foreach ($document->getFieldInvites()->toArray() as $invite) {
            // Depending on SDK version the keys may differ; we match via role_unique_id
            $inviteRoleId = $invite['role_unique_id'] ?? ($invite['role_id'] ?? null);
            if ($inviteRoleId === $roleId) {
                return $invite['id'] ?? $invite['field_invite_unique_id'] ?? '';
            }
        }

        throw new \RuntimeException("Invite for role {$roleName} not found.");
    }

    /**
     * Utility method to get the role ID from the document by role name.
     */
    private function getRoleIdByName(ApiClient $apiClient, string $documentId, string $roleName): string
    {
        /** @var DocumentGetResponse $response */
        $response = $apiClient->send((new DocumentGet())->withDocumentId($documentId));

        $roles = $response->getRoles();
        foreach ($roles as $role) {
            /**@var Role $role*/
            if ($role->getName() === $roleName) {
                return $role->getUniqueId();
            }
        }

        throw new \RuntimeException("Role '{$roleName}' not found in document roles.");
    }

    /**
     * Generate a redirect URL that the signer will follow upon completing
     * their embedded session. The redirect key determines whether to
     * move to the next signer or download the final doc.
     */
    private function makeRedirectUrl(string $documentId, string $nextStep): string
    {
        // Adjust to match your front-end or route structure. The `page` or
        // action might be used to indicate the next flow step.
        // For example:
        //   - nextStep = "signer1" => direct the user to request the link for Signer 1
        //   - nextStep = "signer2"
        //   - nextStep = "finish" => prompt user to download
        return $this->buildSampleUrl([
            'document_id' => $documentId,
            'step' => $nextStep,
            'redirect' => 1,
        ]);
    }

    private function buildSampleUrl(array $params = []): string
    {
        $baseUrl = rtrim((string)config('app.url'), '/') . '/' . self::TARGET_SAMPLE;

        $filtered = array_filter(
            $params,
            static fn ($value) => $value !== null
        );

        if ($filtered !== []) {
            $baseUrl .= '?' . http_build_query($filtered);
        }

        return $baseUrl;
    }

    /**
     * Download the fully signed PDF.
     */
    private function downloadDocument(ApiClient $apiClient, string $documentId): SplFileInfo
    {
        $request = (new DocumentDownloadGet())
            ->withDocumentId($documentId)
            ->withType('collapsed');

        $response = $apiClient->send($request);
        return $response->getFile();
    }

    /**
     * Return the current email invitation statuses for all roles in the document.
     */
    private function getDocumentStatuses(ApiClient $apiClient, string $documentId): array
    {
        /** @var DocumentGetResponse $response */
        $response = $apiClient->send((new DocumentGet())->withDocumentId($documentId));
        $invites = $response->getFieldInvites()->toArray();

        $statuses = [];
        foreach ($invites as $invite) {
            $email = $invite['email'] ?? '';
            $firstStatus = $invite['email_statuses'][0] ?? null;
            $statuses[] = [
                'email'     => $email,
                'timestamp' => $firstStatus ? date('Y-m-d H:i:s', $firstStatus['created_at']) : '',
                'status'    => $firstStatus['status'] ?? 'Pending',
            ];
        }

        return $statuses;
    }
}
