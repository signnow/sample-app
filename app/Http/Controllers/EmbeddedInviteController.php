<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\EmbeddedInviteRequest;
use App\Services\SignNow\DataMapper\EmbeddedInviteDataMapper;
use App\Services\SignNow\EmbeddedInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\ResponseFactory;
use ReflectionException;
use SignNow\Rest\EntityManager\Exception\EntityManagerException;

class EmbeddedInviteController extends Controller
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly EmbeddedInvite $embeddedInvite,
        private readonly EmbeddedInviteDataMapper $embeddedInviteDataMapper,
    ) {
    }

    /**
     * @throws ReflectionException
     * @throws EntityManagerException
     */
    public function create(EmbeddedInviteRequest $request): JsonResponse
    {
        $embeddedInviteDTO = $this->embeddedInviteDataMapper->map($request);

        $result = $this->embeddedInvite->create(
            storage_path('/data/sample.pdf'),
            $embeddedInviteDTO
        );

        return $this->responseFactory->json([
            'data' => [
                'url' => $result->getSigningLink(),
            ],
        ]);
    }
}
