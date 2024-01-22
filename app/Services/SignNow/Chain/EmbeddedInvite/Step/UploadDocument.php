<?php

declare(strict_types=1);

namespace App\Services\SignNow\Chain\EmbeddedInvite\Step;

use App\Data\EmbeddedInvite as EmbeddedInviteData;
use ReflectionException;
use SignNow\Api\Action\Document as DocumentApiAction;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

class UploadDocument extends BaseStep
{
    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function process(EmbeddedInviteData $embeddedInviteData): EmbeddedInviteData
    {
        $documentApiAction = new DocumentApiAction($embeddedInviteData->getAuthentication());

        $document = $documentApiAction->upload($embeddedInviteData->getUploadDocumentPath());

        $embeddedInviteData->setDocumentId($document->getId());

        return $this->toNextStep($embeddedInviteData);
    }
}
