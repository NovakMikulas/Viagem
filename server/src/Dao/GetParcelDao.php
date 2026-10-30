<?php

declare(strict_types=1);

namespace App\Dao;

use PDO;

class GetParcelDao
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function execute(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.label, p.area, p.land_type, p.valid_from,
                    p.cadastral_unit_code, cu.name AS cadastral_unit_name
             FROM parcels p
             LEFT JOIN cadastral_units cu ON cu.code = p.cadastral_unit_code
             WHERE p.id = :id'
        );
        $statement->execute(['id' => $id]);

        $row = $statement->fetch();
        if ($row === false) {
            return null;
        }

        return $row;
    }
}
