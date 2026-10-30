<?php

declare(strict_types=1);

namespace App\Import;

use RuntimeException;

class RuianClient
{
    private const BASE_URL = 'https://ags.cuzk.gov.cz/arcgis/rest/services/RUIAN/MapServer';
    private const LAYER_CADASTRAL_UNIT = 7;
    private const LAYER_MUNICIPALITY = 12;

    public function getCadastralUnitCodesByMunicipality(int $municipalityCode): array
    {
        $codes = $this->queryCodes(self::LAYER_CADASTRAL_UNIT, 'obec = ' . $municipalityCode);

        if (count($codes) === 0) {
            throw new RuntimeException('Obec ' . $municipalityCode . ' nemá v RÚIAN žádná k.ú.');
        }

        return $codes;
    }

    public function getCadastralUnitCodesByDistrict(int $districtCode): array
    {
        $municipalityCodes = $this->queryCodes(self::LAYER_MUNICIPALITY, 'okres = ' . $districtCode);

        if (count($municipalityCodes) === 0) {
            throw new RuntimeException('Okres ' . $districtCode . ' nemá v RÚIAN žádné obce.');
        }

        $where = 'obec IN (' . implode(',', $municipalityCodes) . ')';

        return $this->queryCodes(self::LAYER_CADASTRAL_UNIT, $where);
    }

    private function queryCodes(int $layer, string $where): array
    {
        $parameters = [
            'where' => $where,
            'outFields' => 'kod',
            'returnGeometry' => 'false',
            'f' => 'json',
        ];
        $url = self::BASE_URL . '/' . $layer . '/query?' . http_build_query($parameters);

        $json = file_get_contents($url);
        if ($json === false) {
            throw new RuntimeException('RÚIAN není dostupný, zkus to znovu.');
        }

        $data = json_decode($json, true);
        if (!isset($data['features'])) {
            throw new RuntimeException('RÚIAN vrátil neočekávanou odpověď: ' . $json);
        }

        $codes = [];
        foreach ($data['features'] as $feature) {
            $codes[] = $feature['attributes']['kod'];
        }

        return $codes;
    }
}
