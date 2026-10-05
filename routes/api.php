<?php

use App\Http\Controllers\Api\RoomController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReserveController;

Route::apiResource('rooms', RoomController::class);
Route::post('reserves', [ReserveController::class, 'store']);