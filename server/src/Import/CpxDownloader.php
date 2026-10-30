<?php

declare(strict_types=1);

namespace App\Import;

use RuntimeException;

class CpxDownloader
{
    private const BASE_URL = 'https://services.cuzk.cz/gml/inspire/cpx/epsg-5514/';

    private string $dataDir;

    public function __construct(string $dataDir)
    {
        $this->dataDir = $dataDir;
    }

    public function download(int $cadastralUnitCode, bool $force = false): string
    {
        $target = $this->dataDir . '/' . $cadastralUnitCode . '.zip';

        if (is_file($target) && !$force) {
            return $target;
        }

        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir);
        }

        $url = self::BASE_URL . $cadastralUnitCode . '.zip';

        $content = file_get_contents($url);
        if ($content === false) {
            throw new RuntimeException('Nepodařilo se stáhnout ' . $url);
        }

        file_put_contents($target, $content);

        return $target;
    }
}
