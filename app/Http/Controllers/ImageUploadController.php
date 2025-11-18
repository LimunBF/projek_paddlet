<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Penting untuk menyimpan file

class ImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048', // Maks 2MB
        ]);

        // Simpan gambar ke storage/app/public/posts_images
        // dan dapatkan path-nya (misal: posts_images/randomhash.jpg)
        $imagePath = $request->file('image')->store('posts_images', 'public');

        // Kembalikan URL publik dari gambar ini
        return response()->json([
            'url' => Storage::url($imagePath) // URL publik (misal: /storage/posts_images/randomhash.jpg)
        ], 200);
    }
}