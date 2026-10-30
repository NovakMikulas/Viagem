<?php

declare(strict_types=1);

namespace App\Service;

use App\Dao\GetParcelDao;
use App\Enums\LandType;
use DateTimeImmutable;
use InvalidArgumentException;

class GetParcelService
{
    private GetParcelDao $dao;

    public function __construct(GetParcelDao $dao)
    {
        $this->dao = $dao;
    }

    public function execute(string $id): ?array
    {
        if (!ctype_digit($id) || strlen($id) > 18) {
            throw new InvalidArgumentException('ID parcely musí být kladné celé číslo.');
        }

        $row = $this->dao->execute((int) $id);
        if ($row === null) {
            return null;
        }

        return [
            'id' => $row['id'],
            'label' => $row['label'],
            'cadastral_unit' => [
                'code' => $row['cadastral_unit_code'],
                'name' => $row['cadastral_unit_name'],
            ],
            'area' => $row['area'],
            'land_type' => [
                'code' => $row['land_type'],
                'name' => LandType::labelForCode($row['land_type']),
            ],
            'valid_from' => $this->formatDate($row['valid_from']),
        ];
    }

    private function formatDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $date = new DateTimeImmutable($value);

        return $date->format(DATE_ATOM);
    }
}
