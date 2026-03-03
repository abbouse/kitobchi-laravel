<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyBooks extends Model
{
    protected $table = 'my_books';
    use HasFactory;
    protected $fillable = [
        'user_id',
        'book_id',
        'status',
    ];
    
}