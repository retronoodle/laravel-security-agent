## Context

Multiple Laravel applications need automated threat detection without any per-project custom code. The solution is a Composer-installable package that slots into any Laravel 8+ app via a service provider. It polls the Laravel log file on a schedule, detects suspicious patterns, and dispatches a Claude AI agent (via tool use) to classify and respond to threats.

No existing security infrastructure to integrate with. Queue driver is `sync` in MVP.

## Goals / Non-Goals

**Goals:**
- Composer package installable in any Laravel 8+ project
- Scheduled log polling that extracts suspicious entries without middleware overhead
- Claude API agent with tool use: IP history, block IP, send admin email
- Auto-block high-confidence threats; email alert for uncertain ones
- Publishable config, migrations, and email view
- Audit log of every agent decision

**Non-Goals:**
- Real-time/streaming log monitoring (polling is sufficient for MVP)
- Web UI or dashboard
- Rate limiting or request throttling enforcement beyond blocklist
- Multi-channel alerting (Slack, SMS) — email only
- Machine learning or custom model training
- IPv6 support in blocklist (MVP: IPv4 only)

## Decisions

**1. Package delivery via Composer + Service Provider**
> Why: Standard Laravel pattern. Auto-discovery via `composer.json` `extra.laravel` registers the service provider without host-app config. Publishable assets give host apps full control.
> Alternative: Standalone app users copy into projects — rejected, no upgrade path.

**2. Log polling via Laravel Scheduler (not middleware)**
> Why: Middleware runs on every request and wastes tokens on benign traffic. Polling the log file on a schedule (e.g., every minute) batches detection and only invokes Claude when patterns match.
> Alternative: Middleware-based event dispatch — rejected per requirements.
> Trade-off: Up to 1-minute detection lag, acceptable for MVP.

**3. Claude API via Guzzle HTTP (no Anthropic PHP SDK)**
> Why: Avoids an SDK dependency the host app may not have; Guzzle is already a Laravel/Symfony requirement. Keeps the package lean.
> Alternative: `anthropic-ai/sdk` — can be added later without breaking changes.

**4. Tool use pattern for agent actions**
> Why: Claude tool use lets the agent decide when to call `block_ip`, `get_ip_history`, `get_recent_events`, or `send_alert` — the agent reasons over the evidence before acting.
> Tools are PHP closures registered in `ThreatAgent`; each tool maps to a service method.

**5. Confidence threshold for auto-block**
> Why: Avoids false positives blocking legitimate users. Configurable via `security-agent.confidence_threshold` (default: 0.85).
> Decisions below threshold → email alert only, no block.

**6. `security_events` + `ip_blocklist` as package migrations**
> Why: Package owns its schema. Host app runs `php artisan migrate` after publishing migrations.
> `ip_blocklist` checked in a middleware the host app can optionally register.

## Package Structure

```
laravel-security-agent/
├── src/
│   ├── SecurityAgentServiceProvider.php
│   ├── Console/Commands/MonitorLogs.php
│   ├── Jobs/AnalyzeThreat.php
│   ├── Services/
│   │   ├── ThreatAgent.php        # Claude API + tool loop
│   │   ├── LogParser.php          # Regex pattern matching on log lines
│   │   └── IpBlocklist.php        # DB read/write for ip_blocklist
│   └── Mail/SecurityAlert.php
├── database/migrations/
│   ├── create_security_events_table.php
│   └── create_ip_blocklist_table.php
├── config/security-agent.php
├── resources/views/emails/security-alert.blade.php
└── composer.json
```

## Risks / Trade-offs

- **Log file access** → Package assumes `storage/logs/laravel.log` exists and is readable. Mitigation: configurable log path; graceful skip if file missing.
- **Claude API cost** → Each suspicious batch costs tokens. Mitigation: pattern matching filters noise before calling Claude; configurable min-events-per-batch threshold.
- **sync queue = blocking** → In MVP, `AnalyzeThreat` runs inline. If Claude is slow, the scheduler artisan process blocks. Mitigation: document that async queue driver is recommended for production.
- **False positives** → High confidence threshold (0.85 default) reduces risk. Blocked IPs expire after configurable TTL (default 1 hour).
- **Log rotation** → Polling reads from tail of file using byte offset stored in cache. If log rotates, offset resets to 0. Acceptable for MVP.

## Migration Plan

1. `composer require <vendor>/laravel-security-agent`
2. `php artisan vendor:publish --tag=security-agent-config`
3. `php artisan vendor:publish --tag=security-agent-migrations`
4. `php artisan migrate`
5. Add `ANTHROPIC_API_KEY` and `SECURITY_ADMIN_EMAIL` to `.env`
6. Scheduler auto-registered via service provider

Rollback: `composer remove`, drop migrated tables.

## Open Questions

- Package vendor name (e.g., `timmonaghan/laravel-security-agent`)?
- Should the blocklist middleware be auto-registered or opt-in? (Recommend opt-in for MVP)
- Default log patterns to detect (SQLi, 404 floods, auth failures) — confirm before implementing `LogParser`.
