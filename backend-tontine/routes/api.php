<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UtilisateurController;
use App\Http\Controllers\Api\GroupementController;
use App\Http\Controllers\Api\CotisationController;
use App\Http\Controllers\Api\PretController;
use App\Http\Controllers\Api\RemboursementController;
use App\Http\Controllers\Api\SeanceController;

// ── Routes publiques ────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ── Routes protégées ────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Groupements et cotisations
    Route::apiResource('groupements',    GroupementController::class);
    Route::apiResource('seances',        SeanceController::class);
    Route::apiResource('remboursements', RemboursementController::class)
        ->only(['index', 'store', 'show']);
    Route::apiResource('cotisations', CotisationController::class)
         ->except(['update', 'destroy']);
    Route::put('cotisations/{cotisation}/annuler',
         [CotisationController::class, 'annuler']);
    Route::get('mes-cotisations',
         [CotisationController::class, 'mesCotisations']);

    // Prêts
    Route::apiResource('prets', PretController::class)->except(['update', 'destroy']);
    Route::put('prets/{pret}/valider', [PretController::class, 'valider']);
    Route::put('prets/{pret}/refuser', [PretController::class, 'refuser']);

    // Utilisateurs (admin seulement)
    Route::apiResource('utilisateurs', UtilisateurController::class);

    Route::apiResource('utilisateurs', UtilisateurController::class);
    Route::put('utilisateurs/{utilisateur}/activer',
         [UtilisateurController::class, 'activer']);
    Route::put('utilisateurs/{utilisateur}/desactiver',
         [UtilisateurController::class, 'desactiver']);
});