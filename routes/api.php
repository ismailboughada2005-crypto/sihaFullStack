<?php

use App\Http\Controllers\AdministrateurController;
use App\Http\Controllers\MedecinController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SecretaireController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DoctorAppointmentController;
use Illuminate\Support\Facades\Route;

Route::get('/test-users', function() {
    return response()->json(\App\Models\User::all(['id', 'email', 'name', 'email']));
});

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/stats/counts', [StatsController::class, 'counts']);

    Route::apiResource('patients', PatientController::class);
    Route::apiResource('administrateurs', AdministrateurController::class);
    Route::apiResource('medecins', MedecinController::class);
    Route::apiResource('secretaires', SecretaireController::class);
    Route::apiResource('appointments', AppointmentController::class);

    // ─── Payments Module ─────────────────────────────────────────────────────
    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::get('patients/{patient}/invoices', [InvoiceController::class, 'patientHistory']);

    // Payments
    Route::apiResource('payments', PaymentController::class);
    Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt']);

    // Reports / Analytics
    Route::prefix('reports')->group(function () {
        Route::get('dashboard',        [ReportController::class, 'dashboard']);
        Route::get('monthly-revenue',  [ReportController::class, 'monthlyRevenue']);
        Route::get('daily-revenue',    [ReportController::class, 'dailyRevenue']);
        Route::get('payment-methods',  [ReportController::class, 'paymentMethodBreakdown']);
        Route::get('top-patients',     [ReportController::class, 'topPatients']);
    });

    // ─── Doctor Appointments Module ──────────────────────────────────────────
    Route::prefix('doctor')->group(function () {
        Route::get('appointments', [DoctorAppointmentController::class, 'index']);
        Route::patch('appointments/{appointment}/confirm', [DoctorAppointmentController::class, 'confirm']);
        Route::patch('appointments/{appointment}/cancel', [DoctorAppointmentController::class, 'cancel']);
        Route::patch('appointments/{appointment}/complete', [DoctorAppointmentController::class, 'complete']);
    });
});