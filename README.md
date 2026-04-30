# Laravel Security Agent

AI-powered threat detection for Laravel applications. Monitors logs for suspicious activity and uses a Claude AI agent (via tool use) to classify threats, block IPs, and alert administrators.

## Requirements

- PHP 8.1+
- Laravel 9, 10, or 11
- Guzzle 7+
- An Anthropic API key

## Installation

**1. Require the package**

```bash
composer require timmonaghan/laravel-security-agent
```

**2. Publish the config**

```bash
php artisan vendor:publish --tag=security-agent-config
```

**3. Publish and run the migrations**

```bash
php artisan vendor:publish --tag=security-agent-migrations
php artisan migrate
```

**4. (Optional) Publish the email view**

```bash
php artisan vendor:publish --tag=security-agent-views
```

**5. Add environment variables to `.env`**

```dotenv
LSA_ANTHROPIC_API_KEY=sk-ant-...
LSA_MODEL=claude-sonnet-4-6               # optional, this is the default

SECURITY_ADMIN_EMAILS=admin@example.com,ops@example.com
SECURITY_CONFIDENCE_THRESHOLD=0.85        # 0.0–1.0, default 0.85
SECURITY_BLOCK_TTL_MINUTES=60             # default 60
SECURITY_LOG_PATH=/path/to/storage/logs/laravel.log  # default: storage/logs/laravel.log
SECURITY_SCHEDULE_FREQUENCY=everyMinute  # everyMinute (default), everyFiveMinutes, everyTenMinutes, everyFifteenMinutes, everyThirtyMinutes, hourly

# Admin panel
LSA_ADMIN_ENABLED=true
LSA_ADMIN_PASSWORD=your-secret-password
LSA_ADMIN_PATH=lsa-admin                  # URL path, default: lsa-admin
```

**6. Ensure the Laravel scheduler is running**

```bash
# In cron (recommended for production)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

The package auto-registers the `security-agent:monitor` command in the Laravel scheduler.

## Admin Web Panel

Laravel Security Agent ships with a built-in web panel for monitoring and configuration.

**Access it at:** `https://your-app.com/lsa-admin` (or your configured `LSA_ADMIN_PATH`)

The panel provides:

- **Dashboard** — live counts of security events and blocked IPs, plus a table of the 10 most recent events with IP, pattern type, confidence score, and outcome.
- **Settings** — change the active Claude model and update the Anthropic API key without touching the server. Changes are written directly to your `.env` file.
- **Password protection** — set `LSA_ADMIN_PASSWORD` in `.env` to secure the panel. The panel is disabled (403) if no password is configured.

To use a custom URL path:

```dotenv
LSA_ADMIN_PATH=security-dashboard
```

## How It Works

1. **Log polling** — `security-agent:monitor` runs on the configured schedule and reads new log lines using a byte-offset (stored in cache), so it only processes new entries.
2. **Pattern detection** — Lines are scanned for `sqli`, `auth_brute_force`, and `404_flood` patterns. Matching batches are dispatched as `AnalyzeThreat` jobs.
3. **AI analysis** — `ThreatAgent` sends the suspicious batch to Claude with four tools: `get_ip_history`, `get_recent_events`, `block_ip`, `send_alert`. Claude reasons over the evidence and calls tools as needed (max 10 turns).
4. **Auto-block or alert** — High-confidence threats (≥ threshold) are written to `lsa_ip_blocklist` with an expiry. Lower-confidence threats trigger an admin email.
5. **Audit trail** — Every event and agent decision is stored in `lsa_security_events` with a human-readable summary.
6. **API rate limiting** — Built-in rate limiter prevents runaway Claude API calls under heavy log volume.

## Blocklist Middleware (Optional)

To enforce the IP blocklist on incoming requests, add this to your host app's `app/Http/Middleware`:

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

public function handle($request, $next)
{
    $ip = $request->ip();
    $blocked = DB::table('lsa_ip_blocklist')
        ->where('ip_address', $ip)
        ->where('expires_at', '>', Carbon::now())
        ->exists();

    if ($blocked) {
        abort(403, 'Your IP has been blocked due to suspicious activity.');
    }

    return $next($request);
}
```

Register it in `app/Http/Kernel.php` as needed.

## Rollback

```bash
composer remove timmonaghan/laravel-security-agent
php artisan migrate:rollback  # or drop tables manually
```

## Notes

- Queue driver `sync` is supported (MVP default) but an async driver (Redis, database) is recommended for production to avoid blocking the scheduler process during Claude API calls.
- IPv4 only in this release.
- Database tables are prefixed with `lsa_` (`lsa_security_events`, `lsa_ip_blocklist`) to avoid collisions with host app tables.
