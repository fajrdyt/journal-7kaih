<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    protected $fillable = [
        'code',
        'name',
        'default_activity_context',
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

    /**
     * Hanya ambil kebiasaan yang aktif, urut berdasarkan sort_order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}