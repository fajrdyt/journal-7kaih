<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use Illuminate\Support\Carbon;

class AnalyticsService
{
    public function trackEvent(int $userId, string $eventName, ?array $properties = null): AnalyticsEvent
    {
        return AnalyticsEvent::create([
            'user_id'    => $userId,
            'event_name' => $eventName,
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }
}