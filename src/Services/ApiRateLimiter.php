<?php

namespace Timmonaghan\SecurityAgent\Services;

use Illuminate\Support\Facades\Cache;

class ApiRateLimiter
{
    private const MINUTE_KEY  = 'security_agent.rate.minute.';
    private const DAILY_KEY   = 'security_agent.rate.daily.';

    public function isAllowed(): bool
    {
        $perMinute = (int) config('security-agent.rate_limit.per_minute', 10);
        $dailyMax  = (int) config('security-agent.rate_limit.daily_max', 500);

        $minuteKey = self::MINUTE_KEY . floor(time() / 60);
        $dailyKey  = self::DAILY_KEY . gmdate('Y-m-d');

        $minuteCount = (int) Cache::get($minuteKey, 0);
        $dailyCount  = (int) Cache::get($dailyKey, 0);

        if ($minuteCount >= $perMinute || $dailyCount >= $dailyMax) {
            return false;
        }

        Cache::add($minuteKey, 0, 60);
        Cache::increment($minuteKey);

        Cache::add($dailyKey, 0, 86400);
        Cache::increment($dailyKey);

        return true;
    }
}
