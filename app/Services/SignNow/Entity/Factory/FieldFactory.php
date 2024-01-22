<?php

declare(strict_types=1);

namespace App\Services\SignNow\Entity\Factory;

use App\Data\EmbeddedInviteInput;
use SignNow\Api\Entity\Document\Field\SignatureField;
use SignNow\Api\Entity\Document\Field\TextField;

class FieldFactory
{
    public function makeFirstNameTextField(EmbeddedInviteInput $embeddedInviteInput, string $signerRole): TextField
    {
        return (new TextField())
            ->setName('SignerFirstName')
            ->setLabel('First Name')
            ->setPrefilledText($embeddedInviteInput->getFirstName())
            ->setPageNumber(0)
            ->setRole($signerRole)
            ->setRequired(true)
            ->setHeight(15)
            ->setWidth(210)
            ->setX(158)
            ->setY(76);
    }

    public function makeLastNameTextField(EmbeddedInviteInput $embeddedInviteInput, string $signerRole): TextField
    {
        return (new TextField())
            ->setName('SignerLastName')
            ->setLabel('Last Name')
            ->setPrefilledText($embeddedInviteInput->getLastName())
            ->setPageNumber(0)
            ->setRole($signerRole)
            ->setRequired(true)
            ->setHeight(15)
            ->setWidth(210)
            ->setX(158)
            ->setY(105);
    }

    public function makeCommentTextField(string $signerRole): TextField
    {
        return (new TextField())
            ->setName('Comment')
            ->setLabel('Comment')
            ->setPageNumber(0)
            ->setRole($signerRole)
            ->setRequired(false)
            ->setHeight(50)
            ->setWidth(210)
            ->setX(158)
            ->setY(133);
    }

    public function makeSignatureField(string $signerRole): SignatureField
    {
        return (new SignatureField())
            ->setName('CustomerSignature')
            ->setPageNumber(0)
            ->setRole($signerRole)
            ->setRequired(true)
            ->setHeight(30)
            ->setWidth(150)
            ->setX(158)
            ->setY(250);
    }
}
