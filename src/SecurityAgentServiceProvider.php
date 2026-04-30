<?php

namespace Timmonaghan\SecurityAgent;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Timmonaghan\SecurityAgent\Console\Commands\MonitorLogs;
use Timmonaghan\SecurityAgent\Services\IpBlocklist;
use Timmonaghan\SecurityAgent\Services\ThreatAgent;

class SecurityAgentServiceProvider extends ServiceProvider
{
    public const VALID_FREQUENCIES = [
        'everyMinute',
        'everyFiveMinutes',
        'everyTenMinutes',
        'everyFifteenMinutes',
        'everyThirtyMinutes',
        'hourly',
    ];

    public static function resolveFrequency(string $configured): string
    {
        return in_array($configured, self::VALID_FREQUENCIES, true) ? $configured : 'everyMinute';
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/security-agent.php', 'security-agent');

        $this->app->singleton(ThreatAgent::class, function ($app) {
            return new ThreatAgent(
                $app->make(IpBlocklist::class),
                new Client(['timeout' => 30]),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/security-agent.php' => config_path('security-agent.php'),
        ], 'security-agent-config');

        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'security-agent-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views/' => resource_path('views/vendor/security-agent'),
        ], 'security-agent-views');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'security-agent');

        if ($this->app->runningInConsole()) {
            $this->commands([MonitorLogs::class]);
        }

        $apiKey = config('security-agent.anthropic_api_key') ?: env('ANTHROPIC_API_KEY');
        if ($apiKey) {
            $this->callAfterResolving(\Illuminate\Console\Scheduling\Schedule::class, function ($schedule) {
                $configured = config('security-agent.schedule_frequency', 'everyMinute');
                $frequency = self::resolveFrequency($configured);
                if ($frequency !== $configured) {
                    Log::warning("SecurityAgent: invalid schedule_frequency \"{$configured}\", falling back to everyMinute");
                }
                $schedule->command(MonitorLogs::class)->{$frequency}();
            });
        }
    }
}
