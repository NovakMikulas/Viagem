<?php

declare(strict_types=1);

namespace App\Dao;

use PDO;

class GetCadastralUnitsDao
{
    // Zhruba 10 m – na zoomu, kde se k.ú. zobrazují, je to pod velikostí pixelu
    private const SIMPLIFY_TOLERANCE = 0.0001;

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(): array
    {
        $statement = $this->pdo->prepare(
            'SELECT code, name,
                    ST_AsGeoJSON(ST_SimplifyPreserveTopology(geom, :tolerance), 5) AS geometry,
                    ST_AsGeoJSON(ST_PointOnSurface(geom), 5) AS label_point,
                    ST_XMin(geom) AS min_lon, ST_YMin(geom) AS min_lat,
                    ST_XMax(geom) AS max_lon, ST_YMax(geom) AS max_lat
             FROM cadastral_units
             WHERE geom IS NOT NULL
             ORDER BY name'
        );
        $statement->execute(['tolerance' => self::SIMPLIFY_TOLERANCE]);

        return $statement->fetchAll();
    }
}
