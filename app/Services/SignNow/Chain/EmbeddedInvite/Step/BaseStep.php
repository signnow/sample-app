<?php

declare(strict_types=1);

namespace App\Services\SignNow\Chain\EmbeddedInvite\Step;

use App\Data\EmbeddedInvite as EmbeddedInviteData;

abstract class BaseStep
{
    protected ?BaseStep $nextStep;

    abstract public function process(EmbeddedInviteData $embeddedInviteData): EmbeddedInviteData;

    public function setNextStep(BaseStep $nextStep): void
    {
        $this->nextStep = $nextStep;
    }

    protected function toNextStep(EmbeddedInviteData $embeddedInviteData): EmbeddedInviteData
    {
        return $this->nextStep === null
            ? $embeddedInviteData
            : $this->nextStep->process($embeddedInviteData);
    }
}
