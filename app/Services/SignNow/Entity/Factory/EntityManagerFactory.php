<?php

declare(strict_types=1);

namespace App\Services\SignNow\Entity\Factory;

use App\Services\SignNow\Config\ConfigRepository;
use ReflectionException;
use SignNow\Api\Action\OAuth as SignNowOAuth;
use SignNow\Api\Service\EntityManager\EntityManager;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

readonly class EntityManagerFactory
{
    public function __construct(
        private ConfigRepository $configRepository,
    ) {
    }

    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function authenticate(): EntityManager
    {
        $signNowApiCredentials = $this->configRepository->getCredentials();

        return (new SignNowOAuth($signNowApiCredentials->getHost(), $this->configRepository->getClientName()))
            ->bearerByPassword(
                $signNowApiCredentials->getBasicToken(),
                $signNowApiCredentials->getUser(),
                $signNowApiCredentials->getPassword()
            );
    }
}
