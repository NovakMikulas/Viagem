<?php

declare(strict_types=1);

namespace App\Dao;

use PDO;

class GetParcelTileDao
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(int $z, int $x, int $y): string
    {
        $statement = $this->pdo->prepare(
            "SELECT ST_AsMVT(tile, 'parcels', 4096, 'geom', 'id')
             FROM (
                 SELECT id, label, land_type,
                        ST_AsMVTGeom(ST_Transform(geom, 3857), ST_TileEnvelope(:z, :x, :y), 4096, 64, true) AS geom
                 FROM parcels
                 WHERE geom && ST_Transform(ST_TileEnvelope(:z, :x, :y), 4326)
             ) AS tile"
        );
        $statement->execute(['z' => $z, 'x' => $x, 'y' => $y]);

        $data = $statement->fetchColumn();
        if (is_resource($data)) {
            return stream_get_contents($data);
        }

        return (string) $data;
    }
}
