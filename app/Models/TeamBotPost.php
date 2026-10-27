<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamBotPost extends Model
{
    protected $fillable = ['kind', 'ref_id', 'chat_id', 'thread_id', 'message_id', 'content_hash', 'meta'];

    protected $casts = ['meta' => 'array'];

    public static function for(string $kind, int $refId): ?self
    {
        return static::query()->where('kind', $kind)->where('ref_id', $refId)->first();
    }

    public function metaValue(string $key, mixed $default = null): mixed
    {
        return ($this->meta ?? [])[$key] ?? $default;
    }

    public function putMeta(string $key, mixed $value): void
    {
        $meta = $this->meta ?? [];
        $meta[$key] = $value;
        $this->meta = $meta;
        $this->save();
    }
}
