<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\AnalyseController;
use App\Http\Controllers\StatistiqueController;
use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', fn(Request $r) => response()->json($r->user()));

    // Gestion des utilisateurs (admin uniquement)
    Route::apiResource('users', UserController::class);

    // Espace Admin - Validation des notes
    Route::prefix('notes')->group(function () {
        Route::get('/en-attente', [NoteController::class, 'enAttente']);
        Route::post('/valider/{id}', [NoteController::class, 'valider']);
        Route::post('/rejeter/{id}', [NoteController::class, 'rejeter']);
    });

    // Workflow Rapports
    Route::post('/rapports/test', [RapportController::class, 'testerRapport']);
    Route::get('/rapports', [RapportController::class, 'index']);
    Route::post('/rapports', [RapportController::class, 'store']);
    Route::get('/rapports/tous', [RapportController::class, 'tous']);
    Route::get('/rapports/assignes', [RapportController::class, 'assignes']);
    Route::get('/rapports/{id}/download', [RapportController::class, 'download']);
    
    // Actions Chef / Admin / Enseignant
    Route::post('/rapports/analyse/{id}', [RapportController::class, 'analyserParChef']);
    Route::post('/rapports/affecter/{id}', [RapportController::class, 'affecterEnseignant']);
    Route::post('/rapports/note/{id}', [RapportController::class, 'soumettreNote']);
    Route::post('/rapports/valider-admin/{id}', [RapportController::class, 'validerNoteAdmin']);
    Route::post('/rapports/rejeter-admin/{id}', [RapportController::class, 'rejeterNoteAdmin']);
    Route::post('/rapports/decision-finale/{id}', [RapportController::class, 'decisionFinaleChef']);

    // Analyse de Plagiat (Legacy/Compat)
    Route::get('/analyses/derniere', [AnalyseController::class, 'derniere']);

    // Statistiques (Directeur Adjoint)
    Route::get('/statistiques', [StatistiqueController::class, 'index']);
    Route::get('/statistiques/evolution', [StatistiqueController::class, 'evolution']);

    // Gestion des Thèmes
    Route::post('/themes', [ThemeController::class, 'soumettreTheme']);
    Route::get('/themes/mes', [ThemeController::class, 'mesThemes']);
    Route::get('/themes/en-attente-chef', [ThemeController::class, 'getThemesEnAttenteChef']);
    Route::get('/themes/{id}/similarity', [ThemeController::class, 'checkSimilarity']);
    Route::post('/themes/valider-chef/{id}', [ThemeController::class, 'validerParChef']);
    Route::post('/themes/rejeter-chef/{id}', [ThemeController::class, 'rejeterParChef']);
    Route::get('/themes/en-attente-admin', [ThemeController::class, 'getThemesEnAttenteAdmin']);
    Route::post('/themes/valider-admin/{id}', [ThemeController::class, 'validerParAdmin']);
    Route::post('/themes/rejeter-admin/{id}', [ThemeController::class, 'rejeterParAdmin']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications', [NotificationController::class, 'store']);
    Route::patch('/notifications/{id}/lire', [NotificationController::class, 'marquerLu']);
    Route::patch('/notifications/lire-tout', [NotificationController::class, 'marquerTousLus']);
});
