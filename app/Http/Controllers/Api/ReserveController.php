<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReserveRequest;
use App\Http\Resources\ReserveResource;
use App\Services\ReserveService;


class ReserveController extends Controller
{
    public function __construct(private ReserveService $service)
    {
    }

    public function store(StoreReserveRequest $request)
    {
        $reserve = $this->service->create($request->validated());

        return new ReserveResource($reserve);
    }
}
