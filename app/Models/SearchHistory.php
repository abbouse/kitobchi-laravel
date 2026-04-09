<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class SearchHistory extends Model
{
    protected $fillable = ['user_id', 'session_id', 'result_count', 'result_name', 'result_type', 'text', 'is_draft'];
    
    protected $casts = [
        'is_draft' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}