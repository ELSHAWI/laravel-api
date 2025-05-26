<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pdf extends Model
{
    use HasFactory;
    protected $fillable = [
    'title',
    'subtitle',
    'author',
    'summary',
    'subject',
    'term',
    'grade',
    'college',
    'pdf_path',
    'plan_type'
];
}
