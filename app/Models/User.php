<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Chat;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
   protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'id_number',
        'college',
        'grade',
        'points',
        'term',
        'profile_image',
        'plan_type',
        'plan_status',
        'plan_expires_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function messages()
    {
        return $this->hasMany(Chat::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user has content manager role
     */
    public function isContentManager()
    {
        return $this->role === 'contentManager';
    }

    /**
     * Check if user has editor role
     */
    public function isEditor()
    {
        return $this->role === 'editor';
    }

    /**
     * Check if user has contributor role
     */
    public function isContributor()
    {
        return $this->role === 'contributor';
    }


}

