## Why

The initial MVP ships with several correctness and reliability gaps that would cause real problems for users installing the package into existing apps — from silent DB corruption (table name conflicts) to unbounded Claude API bills under load. These should be fixed before the package is promoted publicly.

## What Changes

- Prefix migration table names with `lsa_` to avoid collisions with host app tables (`lsa_security_events`, `lsa_ip_blocklist`)
- Add API rate limiting and a configurable daily cap to prevent unbounded Claude costs under log noise or attacks
- Stream log file in chunks instead of reading from offset 0, to avoid memory spikes on first run
- Switch `Mail::to()->send()` to `Mail::to()->queue()` inside `AnalyzeThreat` job
- Guard the scheduler registration behind an `ANTHROPIC_API_KEY` presence check
- Tighten the IP extraction regex to exclude version strings (require all four octets to be numeric, not preceded by a letter/slash)

## Capabilities

### New Capabilities
- `api-rate-limiting`: Throttle Claude API calls per-minute and enforce a configurable daily usage cap to bound costs

### Modified Capabilities
- `log-monitor`: Log reading must stream in chunks; scheduler must guard on API key presence
- `ip-blocklist`: Table renamed to `lsa_ip_blocklist`; all references updated
- `security-events`: Table renamed to `lsa_security_events`; all references updated
- `admin-alerts`: Mail dispatch must use queue driver, not synchronous send
- `threat-agent`: IP regex tightened; rate limiter integrated before Claude calls

## Impact

- **Migrations**: Two migration files renamed/altered — existing installs will need a note in upgrade docs
- **Config**: New keys for `rate_limit.per_minute` and `rate_limit.daily_max`
- **Jobs**: `AnalyzeThreat` — mail dispatch change, rate limit check before Claude calls
- **ServiceProvider**: Guard scheduler registration on config/key presence
- **LogParser**: Chunk-based reading replaces full-file read on cold start
- **Models/Queries**: All references to old table names updated
