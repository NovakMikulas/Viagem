<?php

declare(strict_types=1);

namespace App\Service;

use App\Dao\GetParcelTileDao;
use InvalidArgumentException;

class GetParcelTileService
{
    private const MIN_ZOOM = 14;
    private const MAX_ZOOM = 22;

    private GetParcelTileDao $dao;

    public function __construct(GetParcelTileDao $dao)
    {
        $this->dao = $dao;
    }

    public function execute(string $z, string $x, string $y): string
    {
        $zoom = $this->parseNumber($z);
        $tileX = $this->parseNumber($x);
        $tileY = $this->parseNumber($y);

        $tileCount = 2 ** $zoom;
        if ($zoom > self::MAX_ZOOM || $tileX >= $tileCount || $tileY >= $tileCount) {
            throw new InvalidArgumentException('Dlaždice ' . $z . '/' . $x . '/' . $y . ' neexistuje.');
        }

        if ($zoom < self::MIN_ZOOM) {
            return '';
        }

        return $this->dao->execute($zoom, $tileX, $tileY);
    }

    private function parseNumber(string $value): int
    {
        if (!ctype_digit($value) || strlen($value) > 9) {
            throw new InvalidArgumentException('Souřadnice dlaždice musí být celá čísla.');
        }

        return (int) $value;
    }
}
