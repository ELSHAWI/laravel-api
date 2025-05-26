<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;
    protected $fillable = [
        'title', 'subtitle', 'teacher', 'video_path', 'summary_path',
        'for_who', 'term', 'grade', 'college', 'description',
        'image',
        'plan_type'
    ];

}
