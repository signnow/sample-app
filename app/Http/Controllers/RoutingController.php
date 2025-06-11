<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\ControllerFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoutingController
{
    public function __construct(private ControllerFactory $controllerFactory)
    {
    }

    public function routeGet(Request $request): Response
    {
        $sampleName = $request->route('sample_name');
        $ctrl = $this->controllerFactory->createController($sampleName);
        return $ctrl->handleGet($request);
    }

    public function routePost(Request $request): Response
    {
        $sampleName = $request->route('sample_name');
        $ctrl = $this->controllerFactory->createController($sampleName);
        return $ctrl->handlePost($request);
    }
}
