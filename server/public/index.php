<?php

declare(strict_types=1);

use App\Controller\CadastralUnitController;
use App\Controller\ParcelController;
use App\Dao\GetCadastralUnitsDao;
use App\Dao\GetParcelDao;
use App\Dao\GetParcelTileDao;
use App\Database\Database;
use App\Http\Middleware\ErrorHandlerMiddleware;
use App\Http\Request;
use App\Http\Router;
use App\Routes\CadastralUnitRoutes;
use App\Routes\ParcelRoutes;
use App\Service\GetCadastralUnitsService;
use App\Service\GetParcelService;
use App\Service\GetParcelTileService;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

header('Access-Control-Allow-Origin: *');

$errorHandler = new ErrorHandlerMiddleware();

$errorHandler->handle(function () {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    $dotenv->required(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD']);

    $pdo = Database::connect();
    $parcelController = new ParcelController(
        new GetParcelTileService(new GetParcelTileDao($pdo)),
        new GetParcelService(new GetParcelDao($pdo))
    );
    $cadastralUnitController = new CadastralUnitController(
        new GetCadastralUnitsService(new GetCadastralUnitsDao($pdo))
    );

    $router = new Router();
    ParcelRoutes::register($router, $parcelController);
    CadastralUnitRoutes::register($router, $cadastralUnitController);

    $router->dispatch(Request::fromGlobals());
});
