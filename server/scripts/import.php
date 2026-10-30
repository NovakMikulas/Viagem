<?php

declare(strict_types=1);

use App\Dao\ReplaceParcelsDao;
use App\Dao\SaveCadastralUnitDao;
use App\Database\Database;
use App\Import\CpxDownloader;
use App\Import\GmlParcelReader;
use App\Import\RuianClient;
use App\Service\ImportParcelsService;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);

$type = $argv[1] ?? '';
$code = (int) ($argv[2] ?? 0);
$force = in_array('--force', $argv);

$ruian = new RuianClient();

if ($type === 'ku') {
    $codes = [$code];
} elseif ($type === 'obec') {
    $codes = $ruian->getCadastralUnitCodesByMunicipality($code);
} elseif ($type === 'okres') {
    $codes = $ruian->getCadastralUnitCodesByDistrict($code);
} else {
    echo "Použití: php scripts/import.php ku|obec|okres <kód> [--force]\n";
    exit(1);
}

$pdo = Database::connect();

$importService = new ImportParcelsService(
    new CpxDownloader(__DIR__ . '/../data'),
    new GmlParcelReader(),
    new ReplaceParcelsDao($pdo),
    new SaveCadastralUnitDao($pdo)
);

$start = microtime(true);
$totalParcels = 0;
$totalSkipped = 0;
$failed = [];

echo 'K.ú. k importu: ' . count($codes) . "\n";

foreach ($codes as $index => $cadastralUnitCode) {
    $position = '[' . ($index + 1) . '/' . count($codes) . '] ';

    try {
        $result = $importService->execute($cadastralUnitCode, $force);
        $totalParcels += $result['parcels'];
        $totalSkipped += $result['skipped_analogue'];

        echo $position . $cadastralUnitCode . ' ' . $result['name']
            . ': ' . $result['parcels'] . ' parcel'
            . ', přeskočeno analogových: ' . $result['skipped_analogue']
            . ' (' . $result['seconds'] . " s)\n";
    } catch (Throwable $e) {
        $failed[] = $cadastralUnitCode;
        echo $position . $cadastralUnitCode . ' CHYBA: ' . $e->getMessage() . "\n";
    }
}

echo "\nSouhrn\n";
echo 'Parcel celkem:          ' . $totalParcels . "\n";
echo 'Přeskočeno analogových: ' . $totalSkipped . "\n";
echo 'Chyb:                   ' . count($failed);
if (count($failed) > 0) {
    echo ' (' . implode(', ', $failed) . ')';
}
echo "\n";
echo 'Čas:                    ' . round(microtime(true) - $start) . " s\n";
