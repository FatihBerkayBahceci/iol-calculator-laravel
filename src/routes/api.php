<?php

use Illuminate\Support\Facades\Route;
use Docratech\IolCalculator\Http\Controllers\PatientIolCalculationController;

Route::prefix('api/iol-calculator')->middleware(['api', 'auth:sanctum'])->group(function () {
    Route::apiResource('calculations', PatientIolCalculationController::class);
    Route::post('calculations/{calculation}/calculate', [PatientIolCalculationController::class, 'calculate']);
    Route::get('calculations/{calculation}/report', [PatientIolCalculationController::class, 'report']);
});