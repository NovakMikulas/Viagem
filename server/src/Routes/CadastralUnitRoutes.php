<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controller\CadastralUnitController;
use App\Http\Router;

class CadastralUnitRoutes
{
    public static function register(Router $router, CadastralUnitController $controller): void
    {
        $router->get('/api/cadastral-units', [$controller, 'list']);
    }
}
