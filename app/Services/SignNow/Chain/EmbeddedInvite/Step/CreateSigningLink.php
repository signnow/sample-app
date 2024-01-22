<?php

declare(strict_types=1);

namespace App\Services\SignNow\Chain\EmbeddedInvite\Step;

use App\Data\EmbeddedInvite as EmbeddedInviteData;
use ReflectionException;
use SignNow\Api\Action\EmbeddedInvite as EmbeddedInviteApiAction;
use SignNow\Api\Entity\Embedded\Invite\Invite;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

class CreateSigningLink extends BaseStep
{
    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function process(EmbeddedInviteData $embeddedInviteData): EmbeddedInviteData
    {
        $embeddedInviteApiAction = new EmbeddedInviteApiAction($embeddedInviteData->getAuthentication());
        
        $signingLink = $embeddedInviteApiAction->createSigningLink(
            $embeddedInviteData->getDocumentId(),
            $this->findInviteId(
                $embeddedInviteData->getInvites(),
                $embeddedInviteData->getSignerEmail()
            )
        );

        $embeddedInviteData->setSigningLink($signingLink->getLink());

        return $embeddedInviteData;
    }

    private function findInviteId(array $invites, string $signerEmail): string
    {
        foreach ($invites as $invite) {
            /** @var Invite $invite */
            if ($invite->getEmail() === $signerEmail) {
                return $invite->getId();
            }
        }

        return '';
    }
}
