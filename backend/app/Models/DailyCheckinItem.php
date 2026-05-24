<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DailyCheckinItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'daily_checkin_id',
        'habit_id',
        'is_done',
        'activity_context',
    ];

    protected $casts = [
        'is_done' => 'boolean',
    ];

    // ─── Relasi ──────────────────────────────────────────────

    public function dailyCheckin(): BelongsTo
    {
        return $this->belongsTo(DailyCheckin::class);
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    public function validation(): HasOne
    {
        return $this->hasOne(CheckinItemValidation::class);
    }
}