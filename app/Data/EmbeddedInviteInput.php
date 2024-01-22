<?php

declare(strict_types=1);

namespace App\Data;

final readonly class EmbeddedInviteInput
{
    public function __construct(
        private string $firstName,
        private string $lastName,
        private ?string $comment,
    ) {
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function hasComment(): bool
    {
        return !empty($this->comment);
    }
}
