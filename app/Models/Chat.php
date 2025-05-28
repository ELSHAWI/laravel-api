<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
protected $connection = 'pgsql'; // ← Add this line

    protected $fillable = ['user_id', 'message', 'attachment'];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
