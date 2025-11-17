<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\PostController; // <-- TAMBAHKAN INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rute 'auth' (login, register) dari Breeze.
// Ini TIDAK perlu otentikasi.
require __DIR__.'/auth.php';

// RUTE PUBLIK (Tidak perlu login)
Route::get('/boards/{board}', [BoardController::class, 'show']);
Route::get('/boards/{board}/status', [BoardController::class, 'status']);
Route::get('/boards/{board}/posts', [PostController::class, 'index']);
Route::post('/boards/{board}/posts', [PostController::class, 'store']);

// Rute yang WAJIB otentikasi
Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rute Board (Index, Store, Update, Destroy)
    Route::apiResource('boards', BoardController::class)->except('show'); 
    
    // Rute Post (Update, Destroy)
    Route::put('/posts/{post}', [PostController::class, 'update']);
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
    
});