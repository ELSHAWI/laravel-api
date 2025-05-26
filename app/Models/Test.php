<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'duration',
        'difficulty',
        'college',
        'grade',
        'term',
        'plan_type'
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array'
    ];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}