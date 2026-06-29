<?php

declare(strict_types=1);

namespace Samples\PrefillAndOneClickSendingAgreement;

use App\Http\Controllers\SampleControllerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
use SignNow\Api\DocumentInvite\Request\SendInvitePost;
use SignNow\Api\DocumentInvite\Request\Data\To;
use SignNow\Api\DocumentInvite\Request\Data\ToCollection;
use SplFileInfo;
use Symfony\Component\HttpFoundation\Response;

class SampleController implements SampleControllerInterface
{
    private const TEMPLATE_ID = 'e30d6e58c82d43f598e365420f3c665a048a7d81';
    private const ROLE_NAME = 'Recipient 1';

    public function handleGet(Request $request): Response
    {
        return new Response(
            view('PrefillAndOneClickSendingAgreement::index')->render(),
            200,
            ['Content-Type' => 'text/html']
        );
    }

    public function handlePost(Request $request): Response
    {
        $action = $request->input('action');

        $sdk = new Sdk();
        $apiClient = $sdk->build()->authenticate()->getApiClient();

        if ($action === 'send-invite') {
            $name = $request->input('name');
            $email = $request->input('email');
            $documentId = $this->sendInvite($apiClient, $name, $email);
            return new JsonResponse(['status' => 'success', 'document_id' => $documentId]);
        }

        if ($action === 'invite-status') {
            $documentId = $request->input('document_id');
            $statusList = $this->getDocumentStatuses($apiClient, $documentId);
            return new JsonResponse($statusList);
        }

        $file = $this->downloadDocument($apiClient, $request->input('document_id'));
        $content = file_get_contents($file->getRealPath());
        unlink($file->getRealPath());

        return new Response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $file->getFilename() . '"',
        ]);
    }

    private function sendInvite(ApiClient $apiClient, string $name, string $email): string
    {
        $document = $this->createDocumentFromTemplate($apiClient, self::TEMPLATE_ID);
        $this->prefillFields($apiClient, $document->getId(), ['Name' => $name]);

        $roleId = $this->getRoleId($apiClient, $document->getId(), self::ROLE_NAME);

        $to = new ToCollection();
        $to->add(new To(
            email: $email,
            roleId: $roleId,
            role: 'signer',
            order: 1,
            subject: 'Subject',
            message: 'Message',
        ));

        $invite = new SendInvitePost(
            documentId: $document->getId(),
            to: $to,
            from: 'from@email.com',
            subject: 'Subject',
            message: 'Message',
        );
        $apiClient->send($invite);

        return $document->getId();
    }

    private function createDocumentFromTemplate(ApiClient $apiClient, string $templateId): CloneTemplatePostResponse
    {
        $clone = new CloneTemplatePost();
        $clone->withTemplateId($templateId);
        return $apiClient->send($clone);
    }

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

    private function getRoleId(ApiClient $apiClient, string $documentId, string $roleName): string
    {
        /** @var DocumentGetResponse $response */
        $response = $apiClient->send((new DocumentGet())->withDocumentId($documentId));
        foreach ($response->getRoles() as $role) {
            if ($role->getName() === $roleName) {
                return $role->getUniqueId();
            }
        }
        throw new \RuntimeException("Role '{$roleName}' not found.");
    }

    private function downloadDocument(ApiClient $apiClient, string $documentId): SplFileInfo
    {
        $request = (new DocumentDownloadGet())
            ->withDocumentId($documentId)
            ->withType('collapsed');
        $response = $apiClient->send($request);
        return $response->getFile();
    }

    private function getDocumentStatuses(ApiClient $apiClient, string $documentId): array
    {
        /** @var DocumentGetResponse $response */
        $response = $apiClient->send((new DocumentGet())->withDocumentId($documentId));
        $invites = $response->getFieldInvites()->toArray();

        $statuses = [];
        foreach ($invites as $invite) {
            $statuses[] = [
                'name'      => $invite['email'] ?? '',
                'timestamp' => isset($invite['updated']) ? date('Y-m-d H:i:s', (int) $invite['updated']) : '',
                'status'    => $invite['status'] ?? '',
            ];
        }
        return $statuses;
    }
}
