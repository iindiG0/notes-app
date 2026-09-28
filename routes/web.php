<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\NoteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/notes');

// Only for visitors who are NOT logged in
Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    // throttle:10,1 = max 10 login attempts per minute (slows down password guessing)
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

// Only for logged-in users
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Creates notes.index, notes.create, notes.store, notes.edit, notes.update, notes.destroy
    Route::resource('notes', NoteController::class)->except('show');
});
