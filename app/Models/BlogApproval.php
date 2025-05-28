<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogApproval extends Model
{
    use HasFactory;
    protected $connection = 'pgsql'; // ← Add this line

    protected $table = 'blogs_approval';

    protected $fillable = ['title', 'content', 'date', 'author', 'category', 'image', 'read_time'];

}
