<?php

declare(strict_types=1);

namespace App\Services\SignNow\Chain\EmbeddedInvite;

use App\Data\EmbeddedInvite as EmbeddedInviteData;
use App\Services\SignNow\Chain\EmbeddedInvite\Step\AddFields;
use App\Services\SignNow\Chain\EmbeddedInvite\Step\CreateEmbeddedInvite;
use App\Services\SignNow\Chain\EmbeddedInvite\Step\CreateSigningLink;
use App\Services\SignNow\Chain\EmbeddedInvite\Step\GetDocument;
use App\Services\SignNow\Chain\EmbeddedInvite\Step\UploadDocument;
use ReflectionException;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

class EmbeddedInviteExecutionChain
{
    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function execute(EmbeddedInviteData $embeddedInviteData): EmbeddedInviteData
    {
        $uploadStep = new UploadDocument();
        $addFieldsStep = new AddFields();
        $documentStep = new GetDocument();
        $embeddedInviteStep = new CreateEmbeddedInvite();
        $signingLinkStep = new CreateSigningLink();

        $uploadStep->setNextStep($addFieldsStep);
        $addFieldsStep->setNextStep($documentStep);
        $documentStep->setNextStep($embeddedInviteStep);
        $embeddedInviteStep->setNextStep($signingLinkStep);

        return $uploadStep->process($embeddedInviteData);
    }
}
