<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_datetime',
        'end_datetime',
        'location',
        'image_path',
        'category',
        'target_colleges',
        'target_grades',
        'target_terms',
        'target_plans',
        'for_all_students',
        'is_published',
        'created_by'
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'target_colleges' => 'array',
        'target_grades' => 'array',
        'target_terms' => 'array',
        'target_plans' => 'array',
        'is_published' => 'boolean',
        'for_all_students' => 'boolean'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scope for filtering events based on user attributes
    public function scopeForUser($query, $college, $grade, $term, $planType)
    {
        return $query->where(function($q) use ($college, $grade, $term, $planType) {
            $q->where('for_all_students', true)
                ->orWhere(function($q1) use ($college, $grade, $term, $planType) {
                    $q1->whereJsonContains('target_colleges', $college)
                        ->whereJsonContains('target_grades', $grade)
                        ->whereJsonContains('target_terms', $term)
                        ->whereJsonContains('target_plans', $planType);
                });
        });
    }
}