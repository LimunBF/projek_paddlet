<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    use HasFactory;

    /**
     * Kolom yang boleh diisi secara massal.
     */
    protected $fillable = [
        'board_id',
        'user_id',
        'guest_name', // <-- TAMBAHKAN INI
        'content_type',
        'content',
        'caption',
        'position_x',
        'position_y',
        'image_path', // <-- TAMBAHKAN BARIS INI
        'color',    
    ];

    /**
     * Relasi: Post ini milik satu Board.
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Relasi: Post ini dibuat oleh satu User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}