## Why

The application has no automated threat detection. Suspicious activity in logs (brute force, enumeration, SQLi probes) goes unnoticed until damage is done. An AI agent that monitors logs and reacts autonomously closes that gap without requiring manual log review.

## What Changes

- New scheduled log-monitoring command that tails the Laravel log and detects suspicious patterns
- New queued job that invokes a Claude AI agent with tool use to classify and respond to threats
- Agent tools: IP history lookup, recent security events query, IP block action, admin email alert
- New DB tables: `security_events`, `ip_blocklist`
- Admin email notifications via Laravel Mail for ambiguous threats
- Auto-block for high-confidence threats; alert-only for uncertain cases
- All agent decisions stored with human-readable summaries for audit

## Capabilities

### New Capabilities

- `log-monitor`: Scheduled command that tails/polls Laravel logs and emits suspicious-activity events when patterns match known attack signatures
- `threat-agent`: Claude-powered agent job that classifies a security event, correlates with history, and decides to block or alert
- `ip-blocklist`: Tracks blocked IPs with reason, confidence score, expiry, and who/what blocked them
- `security-events`: Stores detected events with raw context, agent analysis, and outcome
- `admin-alerts`: Emails site admins via Laravel Mail when the agent escalates a threat

### Modified Capabilities

## Impact

- New: `app/Console/Commands/MonitorLogs.php`
- New: `app/Jobs/AnalyzeThreat.php`
- New: `app/Services/ThreatAgent.php` (Claude API + tools)
- New: `app/Mail/SecurityAlert.php`
- New: `database/migrations/` — `security_events`, `ip_blocklist`
- New: `resources/views/emails/security-alert.blade.php`
- Dependency: `anthropic-ai/sdk` or raw Guzzle HTTP to Claude API
- Config: `ANTHROPIC_API_KEY`, `SECURITY_ADMIN_EMAIL`, `SECURITY_CONFIDENCE_THRESHOLD`
- Queue: `QUEUE_CONNECTION=sync` (immediate execution in MVP)
