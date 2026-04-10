<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\AnalyseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', fn(Request $r) => response()->json($r->user()));

    // Gestion des utilisateurs (admin uniquement)
    Route::apiResource('users', UserController::class);

    // Espace Enseignant
    Route::get('/rapports/assignes', [RapportController::class, 'assignes']);
    Route::get('/rapports/archives', [RapportController::class, 'archives']);
    Route::get('/rapports/{id}/download', [RapportController::class, 'download']);
    Route::post('/notes', [NoteController::class, 'store']);

    // Espace Étudiant
    Route::get('/rapports', [RapportController::class, 'index']);
    Route::post('/rapports', [RapportController::class, 'store']);
    Route::get('/notes/ma-note', [NoteController::class, 'maNote']);

    // Analyse de Plagiat
    Route::get('/rapports/{id}/analyse', [AnalyseController::class, 'show']);
    Route::get('/analyses/derniere', [AnalyseController::class, 'derniere']);
});
