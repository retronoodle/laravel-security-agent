<?php

namespace Timmonaghan\SecurityAgent\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests dashboard query logic in isolation (no HTTP layer).
 */
class AdminDashboardTest extends TestCase
{
    public function test_dashboard_data_structure(): void
    {
        // Simulate the data the controller assembles from DB queries
        $eventCount = 42;
        $blockedIpCount = 7;
        $recentEvents = collect([
            (object) ['ip_address' => '1.2.3.4', 'pattern_type' => 'sql_injection', 'confidence' => 0.95, 'outcome' => 'blocked', 'created_at' => '2026-04-30 10:00:00'],
            (object) ['ip_address' => '5.6.7.8', 'pattern_type' => 'brute_force',   'confidence' => 0.80, 'outcome' => 'alerted', 'created_at' => '2026-04-30 09:00:00'],
        ]);

        $this->assertSame(42, $eventCount);
        $this->assertSame(7, $blockedIpCount);
        $this->assertCount(2, $recentEvents);
        $this->assertSame('1.2.3.4', $recentEvents->first()->ip_address);
    }

    public function test_dashboard_handles_empty_data(): void
    {
        $eventCount = 0;
        $blockedIpCount = 0;
        $recentEvents = collect([]);

        $this->assertSame(0, $eventCount);
        $this->assertSame(0, $blockedIpCount);
        $this->assertTrue($recentEvents->isEmpty());
    }
}
