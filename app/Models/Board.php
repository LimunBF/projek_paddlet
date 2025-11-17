<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Board extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'visibility',
        'layout_type',
    ];

    public function owner(): BelongsTo
    {
        // Kita spesifikkan 'user_id' sebagai foreign key
        return $this->belongsTo(User::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_members', 'board_id', 'user_id')
                    ->withTimestamps(); // Juga ambil kapan user itu join
    }
}