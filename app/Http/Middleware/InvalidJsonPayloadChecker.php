<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use JsonException;

class InvalidJsonPayloadChecker
{
    /**
     * @throws ValidationException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (empty($request->getContent())) {
            return $next($request);
        }

        try {
            json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['Invalid JSON payload detected.']);
        }

        return $next($request);
    }
}
