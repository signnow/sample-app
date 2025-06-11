<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface SampleControllerInterface
{
    public function handleGet(Request $request): Response;

    public function handlePost(Request $request): Response;
}
