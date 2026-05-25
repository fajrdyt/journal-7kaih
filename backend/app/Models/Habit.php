<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ─── Relasi ──────────────────────────────────────────────

    public function dailyCheckinItems(): HasMany
    {
        return $this->hasMany(DailyCheckinItem::class);
    }

    // ─── Scope ───────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}