<?php

declare(strict_types=1);

namespace App\Services\SignNow\Chain\EmbeddedInvite\Step;

use App\Data\EmbeddedInvite as EmbeddedInviteData;
use ReflectionException;
use SignNow\Api\Action\Document as DocumentApiAction;
use SignNow\Api\Entity\Role;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

class GetDocument extends BaseStep
{
    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function process(EmbeddedInviteData $embeddedInviteData): EmbeddedInviteData
    {
        $documentApiAction = new DocumentApiAction($embeddedInviteData->getAuthentication());

        $document = $documentApiAction->get($embeddedInviteData->getDocumentId());

        $embeddedInviteData->setSignerRoleId(
            $this->findRoleId(
                $document->getRoles(),
                $embeddedInviteData->getSignerRoleName()
            )
        );

        return $this->toNextStep($embeddedInviteData);
    }

    private function findRoleId(array $roles, string $roleName): string
    {
        foreach ($roles as $role) {
            /** @var Role $role */
            if ($role->getName() === $roleName) {
                return $role->getUniqueId();
            }
        }

        return '';
    }
}
