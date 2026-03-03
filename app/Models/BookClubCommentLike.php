<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookClubCommentLike extends Model {
    use HasFactory;

    protected $fillable = ['comment_id', 'user_id'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function comment() {
        return $this->belongsTo(BookClubComment::class, 'comment_id');
    }
}
