<?php

declare(strict_types=1);

namespace App\Routes;

use App\Controller\ParcelController;
use App\Http\Router;

class ParcelRoutes
{
    public static function register(Router $router, ParcelController $controller): void
    {
        $router->get('/api/tiles/{z}/{x}/{y}.pbf', [$controller, 'tile']);
        $router->get('/api/parcels/{id}', [$controller, 'detail']);
    }
}
