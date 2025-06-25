<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Config\Repository;
use Illuminate\Support\Facades\File;

class SamplesProvider
{
    public function __construct(
        private Repository $config
    ) {
    }

    public function getSamplesNames(): array
    {
        return $this->config->get('app.samples_whitelist');
    }

    public function getAllSamplesData(): array
    {
        $data = [];
        $baseSamples = base_path('samples');

        foreach ($this->getSamplesNames() as $sampleName) {
            $metaPath = $baseSamples
                . DIRECTORY_SEPARATOR
                . $sampleName
                . DIRECTORY_SEPARATOR
                . 'Meta.json';

            if (! File::exists($metaPath)) {
                continue;
            }

            $raw = json_decode(File::get($metaPath), true);
            if (! is_array($raw)) {
                continue;
            }

            $data[$sampleName] = [
                'title'            => $raw['Landing headline']    ?? '',
                'description'      => $raw['Landing description'] ?? '',
                'github_php_url'   => $raw['github php']          ?? '',
                'github_java_url'  => $raw['github java']         ?? '',
                'link'             => $raw['Demo']                ?? '',
            ];
        }

        return $data;
    }

    public function hasSample(string $sampleName): bool
    {
        return in_array($sampleName, $this->getSamplesNames());
    }
}
