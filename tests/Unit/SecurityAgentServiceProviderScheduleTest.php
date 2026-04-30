<?php

namespace Timmonaghan\SecurityAgent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Timmonaghan\SecurityAgent\SecurityAgentServiceProvider;

class SecurityAgentServiceProviderScheduleTest extends TestCase
{
    public function test_valid_frequency_is_returned_unchanged(): void
    {
        foreach (SecurityAgentServiceProvider::VALID_FREQUENCIES as $freq) {
            $this->assertSame($freq, SecurityAgentServiceProvider::resolveFrequency($freq));
        }
    }

    public function test_invalid_frequency_falls_back_to_every_minute(): void
    {
        $this->assertSame('everyMinute', SecurityAgentServiceProvider::resolveFrequency('everySecond'));
        $this->assertSame('everyMinute', SecurityAgentServiceProvider::resolveFrequency('invalid'));
        $this->assertSame('everyMinute', SecurityAgentServiceProvider::resolveFrequency(''));
    }

    /**
     * Verifies the guard condition that triggers Log::warning in boot().
     * The actual log call requires a Laravel app container (integration test).
     */
    public function test_invalid_frequency_triggers_warning_condition(): void
    {
        foreach (['everySecond', 'invalid', 'daily', ''] as $bad) {
            $resolved = SecurityAgentServiceProvider::resolveFrequency($bad);
            $this->assertNotSame($bad, $resolved, "Expected '{$bad}' to differ from resolved '{$resolved}' (warning condition)");
        }
    }

    public function test_default_every_minute_is_valid(): void
    {
        $this->assertSame('everyMinute', SecurityAgentServiceProvider::resolveFrequency('everyMinute'));
    }
}
