<?php

declare(strict_types=1);

namespace App\Dao;

use PDO;

class SaveCadastralUnitDao
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(int $code, string $name, ?string $geometryGml): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO cadastral_units (code, name, geom, imported_at)
             VALUES (:code, :name, ST_Multi(ST_Transform(ST_GeomFromGML(:geometry_gml), 4326)), now())
             ON CONFLICT (code) DO UPDATE SET name = EXCLUDED.name, geom = EXCLUDED.geom, imported_at = now()'
        );
        $statement->execute(['code' => $code, 'name' => $name, 'geometry_gml' => $geometryGml]);
    }
}
