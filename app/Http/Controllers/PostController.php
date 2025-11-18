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
     */
    public function index(Board $board)
    {
        // Langsung ambil post, tidak perlu cek otorisasi
        $posts = $board->posts()->with('user:id,name')->latest()->get();
        return response()->json($posts, 200);
    }

    /**
     * Menyimpan post baru ke board tertentu.
     * (Versi Final Gabungan - BENAR)
     */
    public function store(Request $request, Board $board)
    {
        $isGuest =Auth::id() === null;
        // 1. Validasi (Termasuk SEMUA kolom)
        $validated = $request->validate([
            'content' => 'nullable|string|max:1000',
            'image_path' => 'nullable|string',
            'caption' => 'nullable|string',
            'color' => 'nullable|string|max:7', 
            // Validasi nama tamu: Wajib jika Tamu, Boleh kosong jika Admin
            'guest_name' => $isGuest ? 'required|string|max:50' : 'nullable',
        ]);

        // 2. Cek apakah ada isinya
        if (empty($validated['content']) && empty($validated['image_path'])) {
            return response()->json(['message' => 'Catatan harus memiliki konten atau gambar.'], 422);
        }

        // 3. Tentukan Posisi Baru
        $max_y = $board->posts()->max('position_y');
        $new_position_y = $max_y + 1;

        // 4. Buat post (Termasuk SEMUA kolom)
        $post = $board->posts()->create([
            'user_id' => Auth::id(),
            // Jika user login, guest_name null. Jika tamu, pakai inputan.
            'guest_name' => $isGuest ? $validated['guest_name'] : null,
            'content' => $validated['content'] ?? null,
            'image_path' => $validated['image_path'] ?? null,
            'caption' => $validated['caption'] ?? null,
            'color' => $request->color ?? '#ffffff',
            'position_x' => 0,
            'position_y' => $new_position_y,
        ]);

        // 5. Muat relasi user (jika ada)
        $post->load('user:id,name');

        return response()->json($post, 201);
    }


    /**
     * Update post spesifik.
     */
    public function update(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'sometimes|string|nullable',
            'caption' => 'sometimes|string|nullable',
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
     */
    public function destroy(Post $post)
    {
        // KITA PERLU TAHU SIAPA PEMILIK BOARD
        $boardOwnerId = $post->board->user_id;

        if (Auth::check() && ($post->user_id === Auth::id() || $boardOwnerId === Auth::id())) {
            // Jika salah satu benar, izinkan hapus
            $post->delete();
            return response()->json(null, 204);
        }

        // Jika tidak keduanya, tolak
        return response()->json(['message' => 'Tidak diizinkan'], 403);
    }
}