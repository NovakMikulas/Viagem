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

    public function execute(int $code, string $name): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO cadastral_units (code, name, imported_at) VALUES (:code, :name, now())
             ON CONFLICT (code) DO UPDATE SET name = EXCLUDED.name, imported_at = now()'
        );
        $statement->execute(['code' => $code, 'name' => $name]);
    }
}
