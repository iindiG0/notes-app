<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Every route here is automatically prefixed with /api

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

// auth:sanctum = requires "Authorization: Bearer <token>"
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('notes', NoteController::class)->names('api.notes');
});
