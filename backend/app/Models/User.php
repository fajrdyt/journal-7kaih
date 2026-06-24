<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'full_name',
        'username',
        'nisn',
        'email',
        'phone',
        'password',
        'role_id',
        'class_id',
        'avatar_path',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'avatar_path',
    ];

    protected $appends = [
        'avatar_url',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $avatarPath = $this->getRawOriginal(
            'avatar_path'
        );

        if (
            ! is_string($avatarPath) ||
            trim($avatarPath) === ''
        ) {
            return null;
        }

        $normalizedPath = ltrim(
            str_replace('\\', '/', $avatarPath),
            '/'
        );

        return '/storage/'.$normalizedPath;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(
            ClassRoom::class,
            'class_id'
        );
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(
            DailyCheckin::class,
            'student_id'
        );
    }

    public function parentRelations(): HasMany
    {
        return $this->hasMany(
            StudentParentRelation::class,
            'parent_id'
        );
    }

    public function childRelations(): HasMany
    {
        return $this->hasMany(
            StudentParentRelation::class,
            'student_id'
        );
    }
}
