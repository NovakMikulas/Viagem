<?php

declare(strict_types=1);

namespace App\Service;

use App\Dao\ReplaceParcelsDao;
use App\Dao\SaveCadastralUnitDao;
use App\Import\CpxDownloader;
use App\Import\GmlParcelReader;

class ImportParcelsService
{
    private CpxDownloader $downloader;
    private GmlParcelReader $reader;
    private ReplaceParcelsDao $replaceParcelsDao;
    private SaveCadastralUnitDao $saveCadastralUnitDao;

    public function __construct(
        CpxDownloader $downloader,
        GmlParcelReader $reader,
        ReplaceParcelsDao $replaceParcelsDao,
        SaveCadastralUnitDao $saveCadastralUnitDao
    ) {
        $this->downloader = $downloader;
        $this->reader = $reader;
        $this->replaceParcelsDao = $replaceParcelsDao;
        $this->saveCadastralUnitDao = $saveCadastralUnitDao;
    }

    public function execute(int $cadastralUnitCode, bool $forceDownload): array
    {
        $start = microtime(true);

        $zipPath = $this->downloader->download($cadastralUnitCode, $forceDownload);

        $parcelCount = $this->replaceParcelsDao->execute($cadastralUnitCode, $this->reader->read($zipPath));

        $this->saveCadastralUnitDao->execute(
            $cadastralUnitCode,
            $this->reader->getCadastralUnitName(),
            $this->reader->getCadastralUnitGeometryGml()
        );

        return [
            'name' => $this->reader->getCadastralUnitName(),
            'parcels' => $parcelCount,
            'skipped_analogue' => $this->reader->getSkippedAnalogueCount(),
            'seconds' => round(microtime(true) - $start, 1),
        ];
    }
}
