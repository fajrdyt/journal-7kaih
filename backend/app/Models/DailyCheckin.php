<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyCheckin extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'student_id',
        'checkin_date',
        'notes',
        'submitted_at',
    ];

    protected $casts = [
        'checkin_date' => 'date',
        'submitted_at' => 'datetime',
    ];


    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DailyCheckinItem::class);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('checkin_date', [$from, $to]);
    }


    public function getDoneCountAttribute(): int
    {
        return $this->items->where('is_done', true)->count();
    }
}