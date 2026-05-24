<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckinItemValidation extends Model
{
    // Tabel tidak punya updated_at, hanya validated_at
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

    // Relasi ke DailyCheckinItem
    public function checkinItem()
    {
        return $this->belongsTo(DailyCheckinItem::class, 'daily_checkin_item_id');
    }

    // Relasi ke User (validator = parent/teacher)
    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }
}