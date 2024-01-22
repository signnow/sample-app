<?php

declare(strict_types=1);

namespace App\Services\SignNow;

use App\Data\EmbeddedInvite as EmbeddedInviteData;
use App\Data\EmbeddedInviteInput;
use App\Services\SignNow\Builder\EmbeddedInviteBuilder;
use App\Services\SignNow\Config\ConfigRepository;
use App\Services\SignNow\Entity\Factory\FieldFactory;
use ReflectionException;
use SignNow\Api\Entity\Document\Field\SignatureField;
use SignNow\Api\Entity\Document\Field\TextField;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

class EmbeddedInvite
{
    public function __construct(
        private readonly EmbeddedInviteBuilder $embeddedInviteBuilder,
        private readonly FieldFactory $fieldFactory,
        private readonly ConfigRepository $configRepository,
    ) {
    }

    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function create(
        string $downloadDocumentPath,
        EmbeddedInviteInput $embeddedInviteInput,
    ): EmbeddedInviteData {
        $signerRole = $this->configRepository->getSignerRole();
        $signerEmail = $this->configRepository->getSignerEmail();
        $redirectUrl = $this->configRepository->getRedirectUrl();

        $firstNameField = $this->fieldFactory->makeFirstNameTextField($embeddedInviteInput, $signerRole);
        $lastNameField = $this->fieldFactory->makeLastNameTextField($embeddedInviteInput, $signerRole);
        $signatureField = $this->fieldFactory->makeSignatureField($signerRole);
        $commentField = $this->fieldFactory->makeCommentTextField($signerRole);
        if ($embeddedInviteInput->hasComment()) {
            $commentField->setPrefilledText($embeddedInviteInput->getComment());
        }

        return $this->embeddedInviteBuilder
            ->reset()
            ->authenticate()
            ->setUploadDocumentPath($downloadDocumentPath)
            ->addDocumentField($firstNameField)
            ->addDocumentField($lastNameField)
            ->addDocumentField($commentField)
            ->addDocumentField($signatureField)
            ->setSignerFirstName($embeddedInviteInput->getFirstName())
            ->setSignerLastName($embeddedInviteInput->getLastName())
            ->setSignerEmail($signerEmail)
            ->setSignerRoleName($signerRole)
            ->setRedirectUrl($redirectUrl)
            ->makeEmbeddedInviteLink();
    }
}
