<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Service\GetParcelService;
use App\Service\GetParcelTileService;

class ParcelController
{
    private GetParcelTileService $getParcelTileService;
    private GetParcelService $getParcelService;

    public function __construct(GetParcelTileService $getParcelTileService, GetParcelService $getParcelService)
    {
        $this->getParcelTileService = $getParcelTileService;
        $this->getParcelService = $getParcelService;
    }

    public function tile(Request $request): void
    {
        $tile = $this->getParcelTileService->execute(
            $request->getRouteParam('z'),
            $request->getRouteParam('x'),
            $request->getRouteParam('y')
        );

        Response::vectorTile($tile);
    }

    public function detail(Request $request): void
    {
        $id = $request->getRouteParam('id');
        $parcel = $this->getParcelService->execute($id);

        if ($parcel === null) {
            Response::error('Parcela neexistuje.', 404);
            return;
        }

        Response::json($parcel);
    }
}
