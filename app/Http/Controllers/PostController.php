<?php

namespace App\Http\Controllers;

use App\Models\Board;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class PostController extends Controller
{
    /**
     * Menampilkan semua post di board tertentu.
     * Route: GET /api/boards/{board}/posts
     */
    public function index(Board $board)
    {
        // Langsung ambil post, tidak perlu cek otorisasi
        $posts = $board->posts()->with('user:id,name')->latest()->get();
        return response()->json($posts, 200);
    }

    /**
     * Menyimpan post baru ke board tertentu.
     * Route: POST /api/boards/{board}/posts
     */
    // MENJADI INI:
    public function store(Request $request, Board $board)
    {
        // 1. Validasi input (Langsung ke validasi)
        $validated = $request->validate([
            'content' => 'required|string',
            'content_type' => 'nullable|string|in:text,image,link',
            'caption' => 'nullable|string',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
        ]);

        // 2. Buat post
        $post = $board->posts()->create([
            'user_id' => Auth::id(), // Ajaib: 'null' jika tamu, ID jika login
            'content' => $validated['content'],
            'content_type' => $validated['content_type'] ?? 'text',
            'caption' => $validated['caption'] ?? null,
            'position_x' => $validated['position_x'] ?? 0,
            'position_y' => $validated['position_y'] ?? 0,
        ]);

        // 3. Muat relasi user (jika ada)
        $post->load('user:id,name');

        return response()->json($post, 201);
    }


    /**
     * Update post spesifik.
     * Route: PUT /api/posts/{post}
     */
    public function update(Request $request, Post $post)
    {
        // 1. Otorisasi: Apakah user ini pemilik POST?
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        // 2. Validasi (hanya update sebagian)
        $validated = $request->validate([
            'content' => 'sometimes|required|string',
            'caption' => 'nullable|string',
            'position_x' => 'sometimes|integer',
            'position_y' => 'sometimes|integer',
        ]);

        // 3. Update
        $post->update($validated);
        
        // 4. Muat relasi user dan kembalikan
        $post->load('user:id,name');
        return response()->json($post, 200); // 200 = OK
    }

    /**
     * Hapus post spesifik.
     * Route: DELETE /api/posts/{post}
     */
    public function destroy(Post $post)
    {
        // KITA PERLU TAHU SIAPA PEMILIK BOARD
        $boardOwnerId = $post->board->user_id;

        // Otorisasi: User boleh hapus JIKA:
        // 1. Dia adalah pemilik post INI
        // ATAU
        // 2. Dia adalah pemilik BOARD tempat post ini berada
        if ($post->user_id === Auth::id() || $boardOwnerId === Auth::id()) {
            // Jika salah satu benar, izinkan hapus
            $post->delete();
            return response()->json(null, 204);
        }

        // Jika tidak keduanya, tolak
        return response()->json(['message' => 'Tidak diizinkan'], 403);
    }
}