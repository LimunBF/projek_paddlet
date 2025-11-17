<?php

namespace App\Http\Controllers;

use App\Models\Board;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller; // <-- Ini use statement yang kita tambahkan

class BoardController extends Controller
{
    /**
     * Menampilkan daftar board milik user yang sedang login.
     */
    public function index()
    {
        $user = Auth::user();
        $boards = $user->boards()->latest()->get();
        return $boards;
    }

    /**
     * Menyimpan board baru ke database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'layout_type' => 'nullable|string|in:wall,grid,stream',
        ]);

        $user = $request->user();

        $board = $user->boards()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'layout_type' => $validated['layout_type'] ?? 'wall',
        ]);

        return response()->json($board, 201);
    }

    /**
     * Menampilkan satu board spesifik.
     */
    public function show(Board $board)
    {
        // Langsung kembalikan data board
        return $board;
    }

    /**
     * Update board yang sudah ada.
     */
    public function update(Request $request, Board $board)
    {
        // 1. Otorisasi: Cek kepemilikan
        if ($board->user_id !== Auth::id()) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        // 2. Validasi input
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'sometimes|string|in:private,public,link',
            'layout_type' => 'sometimes|string|in:wall,grid,stream',
        ]);

        // 3. Update board
        $board->update($validated);

        // 4. Kembalikan data board yang sudah di-update
        return response()->json($board, 200);
    }

    /**
     * Menghapus board.
     */
    public function destroy(Board $board)
    {
        // 1. Otorisasi: Cek kepemilikan
        if ($board->user_id !== Auth::id()) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        // 2. Hapus board
        $board->delete();

        // 3. Kembalikan respons "No Content"
        return response()->json(null, 204);
    }

    /**
     * Method ringan untuk polling status board.
     * Route: GET /api/boards/{board}/status
     */
    public function status(Board $board)
    {
        // Otorisasi sudah dihapus. Langsung ambil data.
        $postCount = $board->posts()->count();

        $lastUpdated = $board->updated_at;

        return response()->json([
            'total_posts' => $postCount,
            'last_updated' => $lastUpdated,
        ]);
    }
}