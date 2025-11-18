<?php

use App\Http\Controllers\BoardController;
use App\Http\Controllers\PostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/boards/{board}', [BoardController::class, 'show']);
Route::get('/boards/{board}/status', [BoardController::class, 'status']);

// TAMBAHKAN BARIS INI (Agar tamu bisa update judul/deskripsi)
Route::put('/boards/{board}', [BoardController::class, 'update']); 


Route::put('/posts/{post}', [PostController::class, 'update']);
Route::get('/boards/{board}/posts', [PostController::class, 'index']);
Route::post('/boards/{board}/posts', [PostController::class, 'store']);


Route::middleware('auth:sanctum')->group(function () {
    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rute manajemen Board
    // Kita kecualikan 'show' dan 'update' karena sudah ada di publik
    Route::apiResource('boards', BoardController::class)->except(['show', 'update']);
    
    // Rute Post
    // Kita buat 'update' post tetap publik di controller, tapi rute-nya 
    // sebenarnya aman untuk dibuka karena controller-nya yang mengatur logika.
    // Tapi untuk drag-and-drop tamu, kita perlu buka rute post update juga.
    
    // PINDAHKAN INI KE PUBLIK JUGA JIKA INGIN DRAG-DROP TAMU BERJALAN LANCAR
    Route::put('/posts/{post}', [PostController::class, 'update']);
    
    Route::delete('/posts/{post}', [PostController::class, 'destroy']);
});

Route::put('/posts/{post}', [PostController::class, 'update']);