<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->onDelete('cascade'); // Relasi ke board
            // INI BARIS YANG BENAR
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            $table->string('content_type')->default('text'); // 'text', 'image', 'link', 'file'
            $table->text('content'); // Bisa berisi teks, URL gambar, atau JSON
            $table->integer('position_x')->default(0); // Untuk layout 'wall' (drag-drop)
            $table->integer('position_y')->default(0); // Untuk layout 'wall' (drag-drop)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
