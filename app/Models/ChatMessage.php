<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'message', 'image', 'response', 'is_ai', 'is_read'];

    /**
     * Rasmning to'liq URL manzili (ilova uchun).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (empty($this->image)) {
            return null;
        }

        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }

    protected $casts = [
        'response' => 'array',  // JSON avto arrayga aylantiriladi
        'is_ai' => 'boolean',
        'is_read' => 'boolean',
    ];
    
    public function items()
    {
        return $this->hasMany(ChatMessageItem::class, 'chat_message_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}