<?php

declare(strict_types=1);

namespace App\Services\SignNow\Config;

use App\Data\ApiCredentials;
use Illuminate\Contracts\Config\Repository as Config;

readonly class ConfigRepository
{
    private const CLIENT_NAME = 'signNow SDK Sample Application';

    public function __construct(
        private Config $config
    ) {
    }

    public function getCredentials(): ApiCredentials
    {
        $signNowApi = $this->config->get('signnow.api');
        
        return new ApiCredentials(
            $signNowApi['host'],
            $signNowApi['basic_token'],
            $signNowApi['user'],
            $signNowApi['password']
        );
    }

    public function getSignerRole(): string
    {
        return $this->config->get('signnow.api.signer_role');
    }

    public function getSignerEmail(): string
    {
        return $this->config->get('signnow.api.signer_email');
    }

    public function getRedirectUrl(): string
    {
        return $this->config->get('signnow.api.redirect_url');
    }

    public function getClientName(): string
    {
        return self::CLIENT_NAME;
    }
}
