<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\FieldController;
use App\Http\Controllers\Api\PipelineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::get('/accounts/{uuid}', [AccountController::class, 'show']);
    Route::get('/accounts/{uuid}/activity', [AccountController::class, 'activity']);
    Route::get('/pipelines', [PipelineController::class, 'index']);
    Route::get('/fields', [FieldController::class, 'index']);
});
