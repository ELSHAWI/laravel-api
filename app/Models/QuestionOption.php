<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory;
    protected $connection = 'pgsql'; // ← Add this line

    protected $fillable = [
        'question_id',
        'text',
        'is_correct'
    ];

    protected $casts = [
        'text' => 'array'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}