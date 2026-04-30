<?php

namespace Timmonaghan\SecurityAgent\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class IpBlocklist
{
    public function isBlocked(string $ip): bool
    {
        return DB::table('ip_blocklist')
            ->where('ip_address', $ip)
            ->where('expires_at', '>', Carbon::now())
            ->exists();
    }

    public function block(string $ip, string $reason, float $confidence, int $ttlMinutes): void
    {
        $now = Carbon::now();

        DB::table('ip_blocklist')->upsert(
            [
                'ip_address' => $ip,
                'reason'     => $reason,
                'confidence' => $confidence,
                'blocked_by' => 'agent',
                'blocked_at' => $now,
                'expires_at' => $now->copy()->addMinutes($ttlMinutes),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['ip_address'],
            ['reason', 'confidence', 'blocked_by', 'blocked_at', 'expires_at', 'updated_at']
        );
    }
}
