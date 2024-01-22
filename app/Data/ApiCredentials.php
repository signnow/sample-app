<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ApiCredentials
{
    public function __construct(
        public string $host,
        public string $basicToken,
        public string $user,
        public string $password,
    ) {
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getBasicToken(): string
    {
        return $this->basicToken;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
