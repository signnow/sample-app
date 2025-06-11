<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\SampleNotFound;
use App\Http\Controllers\SampleControllerInterface;

class ControllerFactory
{
    public function __construct(
        private SamplesProvider $samplesProvider,
    ) {
    }

    public function createController(string $sampleName): SampleControllerInterface
    {
        if (!$this->samplesProvider->hasSample($sampleName)) {
            throw new SampleNotFound();
        }
        $className = 'Samples\\' . $sampleName . '\SampleController';

        $ctrl = new $className();

        if ($ctrl instanceof SampleControllerInterface) {
            return $ctrl;
        } else {
            throw new SampleNotFound();
        }
    }
}
