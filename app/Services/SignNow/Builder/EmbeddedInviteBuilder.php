<?php

declare(strict_types=1);

namespace App\Services\SignNow\Builder;

use App\Data\EmbeddedInvite as EmbeddedInviteData;
use App\Services\SignNow\Chain\EmbeddedInvite\EmbeddedInviteExecutionChain;
use App\Services\SignNow\Entity\Factory\EntityManagerFactory;
use ReflectionException;
use SignNow\Api\Entity\Document\Field\AbstractField;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

class EmbeddedInviteBuilder
{
    private EmbeddedInviteData $embeddedInviteData;

    public function __construct(
        private readonly EntityManagerFactory $entityManagerFactory,
        private readonly EmbeddedInviteExecutionChain $embeddedInviteExecutionChain,
    ) {
    }

    public function reset(): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData = new EmbeddedInviteData();

        return $this;
    }

    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function authenticate(): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData->setAuthentication(
            $this->entityManagerFactory->authenticate()
        );

        return $this;
    }

    public function setUploadDocumentPath(string $path): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData->setUploadDocumentPath($path);

        return $this;
    }

    public function addDocumentField(AbstractField $field): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData->addField($field);

        return $this;
    }

    public function setSignerEmail(string $signerEmail): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData->setSignerEmail($signerEmail);

        return $this;
    }

    public function setSignerFirstName(string $signerFirstName): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData->setSignerFirstName($signerFirstName);

        return $this;
    }

    public function setSignerLastName(string $signerLastName): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData->setSignerLastName($signerLastName);

        return $this;
    }

    public function setSignerRoleName(string $signerRole): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData->setSignerRoleName($signerRole);

        return $this;
    }

    public function setRedirectUrl(string $redirectUrl): EmbeddedInviteBuilder
    {
        $this->embeddedInviteData->setRedirectUrl($redirectUrl);

        return $this;
    }

    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function makeEmbeddedInviteLink(): EmbeddedInviteData
    {
        return $this->embeddedInviteExecutionChain->execute($this->embeddedInviteData);
    }
}
