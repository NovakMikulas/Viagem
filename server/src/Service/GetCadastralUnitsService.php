<?php

declare(strict_types=1);

namespace App\Service;

use App\Dao\GetCadastralUnitsDao;

class GetCadastralUnitsService
{
    private GetCadastralUnitsDao $dao;

    public function __construct(GetCadastralUnitsDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Vrací GeoJSON FeatureCollection. Každé k.ú. má dva prvky: hranici (polygon)
     * a bod pro popisek, aby se název na mapě zobrazil jen jednou a uvnitř území.
     */
    public function execute(): array
    {
        $features = [];

        foreach ($this->dao->execute() as $row) {
            $properties = [
                'code' => $row['code'],
                'name' => $row['name'],
                'bbox' => [
                    (float) $row['min_lon'],
                    (float) $row['min_lat'],
                    (float) $row['max_lon'],
                    (float) $row['max_lat'],
                ],
            ];

            $features[] = $this->feature($row['geometry'], $properties);
            $features[] = $this->feature($row['label_point'], $properties);
        }

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    private function feature(string $geometryJson, array $properties): array
    {
        return [
            'type' => 'Feature',
            'geometry' => json_decode($geometryJson, true),
            'properties' => $properties,
        ];
    }
}
