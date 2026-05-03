<?php

use App\Http\Controllers\AdministrateurController;
use App\Http\Controllers\MedecinController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SecretaireController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StatsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/stats/counts', [StatsController::class, 'counts']);
    
    Route::apiResource('patients',PatientController::class);
    Route::apiResource('administrateurs',AdministrateurController::class);
    Route::apiResource('medecins',MedecinController::class);
    Route::apiResource('secretaires',SecretaireController::class);
    Route::apiResource('appointments', AppointmentController::class);
});