<?php

declare(strict_types=1);

namespace App\Dao;

use PDO;
use Throwable;

class ReplaceParcelsDao
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(int $cadastralUnitCode, iterable $parcels): int
    {
        $this->pdo->beginTransaction();

        try {
            $delete = $this->pdo->prepare('DELETE FROM parcels WHERE cadastral_unit_code = :code');
            $delete->execute(['code' => $cadastralUnitCode]);

            $insert = $this->pdo->prepare(
                'INSERT INTO parcels (id, cadastral_unit_code, label, area, land_type, valid_from, geom)
                 VALUES (:id, :cadastral_unit_code, :label, :area, :land_type, :valid_from,
                         ST_Multi(ST_Transform(ST_GeomFromGML(:geometry_gml), 4326)))'
            );

            $count = 0;
            foreach ($parcels as $parcel) {
                $insert->execute($parcel);
                $count++;
            }

            $this->pdo->commit();

            return $count;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
