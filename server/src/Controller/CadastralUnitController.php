<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Service\GetCadastralUnitsService;

class CadastralUnitController
{
    private GetCadastralUnitsService $getCadastralUnitsService;

    public function __construct(GetCadastralUnitsService $getCadastralUnitsService)
    {
        $this->getCadastralUnitsService = $getCadastralUnitsService;
    }

    public function list(Request $request): void
    {
        Response::json($this->getCadastralUnitsService->execute());
    }
}
