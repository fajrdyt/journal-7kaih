<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DailyCheckinItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'daily_checkin_id',
        'habit_id',
        'is_done',
    ];

    protected $casts = [
        'is_done' => 'boolean',
    ];

    public function dailyCheckin(): BelongsTo
    {
        return $this->belongsTo(DailyCheckin::class);
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    public function validations(): HasMany
    {
        return $this->hasMany(CheckinItemValidation::class, 'daily_checkin_item_id');
    }

    public function validation(): HasOne
    {
        return $this->hasOne(CheckinItemValidation::class, 'daily_checkin_item_id');
    }

    public function parentValidation(): HasOne
    {
        return $this->hasOne(CheckinItemValidation::class, 'daily_checkin_item_id')
            ->where('validator_role', 'orang_tua');
    }

    public function teacherValidation(): HasOne
    {
        return $this->hasOne(CheckinItemValidation::class, 'daily_checkin_item_id')
            ->where('validator_role', 'guru');
    }
}