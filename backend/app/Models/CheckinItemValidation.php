<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckinItemValidation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'daily_checkin_item_id',
        'validator_id',
        'validator_role',
        'validation_source',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    // ─── Relasi ──────────────────────────────────────────────

    public function checkinItem(): BelongsTo
    {
        return $this->belongsTo(DailyCheckinItem::class, 'daily_checkin_item_id');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    // ─── Scope ───────────────────────────────────────────────

    public function scopeParent($query)
    {
        return $query->where('validator_role', 'orang_tua');
    }

    public function scopeTeacher($query)
    {
        return $query->where('validator_role', 'guru');
    }
}