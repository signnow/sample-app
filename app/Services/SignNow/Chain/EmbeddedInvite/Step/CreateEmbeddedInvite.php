<?php

declare(strict_types=1);

namespace App\Services\SignNow\Chain\EmbeddedInvite\Step;

use App\Data\EmbeddedInvite as EmbeddedInviteData;
use ReflectionException;
use SignNow\Api\Action\EmbeddedInvite as EmbeddedInviteApiAction;
use SignNow\Api\Entity\Embedded\Invite\InviteRequest as EmbeddedInviteRequestData;
use SignNow\Api\Service\OAuth\AuthMethod\Method\None;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

class CreateEmbeddedInvite extends BaseStep
{
    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function process(EmbeddedInviteData $embeddedInviteData): EmbeddedInviteData
    {
        $embeddedInviteApiAction = new EmbeddedInviteApiAction($embeddedInviteData->getAuthentication());

        $embeddedInviteRequest = (new EmbeddedInviteRequestData())
            ->setEmail($embeddedInviteData->getSignerEmail())
            ->setRoleId($embeddedInviteData->getSignerRoleId())
            ->setOrder(1)
            ->setAuthMethod(new None())
            ->setFirstName($embeddedInviteData->getSignerFirstName())
            ->setLastName($embeddedInviteData->getSignerLastName())
            ->setRedirectUri($embeddedInviteData->getRedirectUrl())
            ->setDeclineRedirectUri($embeddedInviteData->getRedirectUrl())
            ->setForceNewSignature(1);

        $createdInvites = $embeddedInviteApiAction->create(
            $embeddedInviteData->getDocumentId(),
            [$embeddedInviteRequest]
        );

        $embeddedInviteData->setInvites($createdInvites->getInvites());

        return $this->toNextStep($embeddedInviteData);
    }
}
