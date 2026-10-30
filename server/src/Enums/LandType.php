<?php

declare(strict_types=1);

namespace App\Enums;

enum LandType: string
{
    case ArableGround = 'ArableGround';
    case Hopgarden = 'Hopgarden';
    case Vineyard = 'Vineyard';
    case Garden = 'Garden';
    case Orchard = 'Orchard';
    case Grassland = 'Grassland';
    case Forest = 'Forest';
    case WaterArea = 'WaterArea';
    case BuiltUpArea = 'BuiltUpArea';
    case OtherArea = 'OtherArea';

    public function label(): string
    {
        return match ($this) {
            LandType::ArableGround => 'Orná půda',
            LandType::Hopgarden => 'Chmelnice',
            LandType::Vineyard => 'Vinice',
            LandType::Garden => 'Zahrada',
            LandType::Orchard => 'Ovocný sad',
            LandType::Grassland => 'Trvalý travní porost',
            LandType::Forest => 'Lesní pozemek',
            LandType::WaterArea => 'Vodní plocha',
            LandType::BuiltUpArea => 'Zastavěná plocha a nádvoří',
            LandType::OtherArea => 'Ostatní plocha',
        };
    }

    public static function labelForCode(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        $landType = LandType::tryFrom($code);
        if ($landType === null) {
            return $code;
        }

        return $landType->label();
    }
}
