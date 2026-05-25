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

    // ─── Relasi ──────────────────────────────────────────────

    public function dailyCheckin(): BelongsTo
    {
        return $this->belongsTo(DailyCheckin::class);
    }

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }

    /**
     * Relasi baru:
     * Satu item bisa punya beberapa validasi:
     * - orang_tua
     * - guru
     */
    public function validations(): HasMany
    {
        return $this->hasMany(CheckinItemValidation::class, 'daily_checkin_item_id');
    }

    /**
     * Backward compatibility untuk kode lama yang masih memanggil validation.
     * Ini mengambil salah satu validasi pertama saja.
     * Nanti service/controller tetap sebaiknya pakai validations().
     */
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