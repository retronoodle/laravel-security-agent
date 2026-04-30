## 1. Table Rename

- [x] 1.1 Rename migration for `security_events` → `lsa_security_events` (update migration file and schema call)
- [x] 1.2 Rename migration for `ip_blocklist` → `lsa_ip_blocklist` (update migration file and schema call)
- [x] 1.3 Update `SecurityEvent` model `$table` property to `lsa_security_events`
- [x] 1.4 Update `IpBlocklist` model `$table` property to `lsa_ip_blocklist`
- [x] 1.5 Grep codebase for any raw table name strings (`security_events`, `ip_blocklist`) and update all references

## 2. Scheduler Guard

- [x] 2.1 In `SecurityAgentServiceProvider::boot()`, wrap the scheduler registration in a check that `config('security-agent.anthropic_key')` or `env('ANTHROPIC_API_KEY')` is non-empty

## 3. Log File Streaming

- [x] 3.1 Rewrite `LogParser::getNewLines()` to use `fopen`/`fseek`/`fread` in 8 KB chunks instead of `file_get_contents` from offset 0
- [x] 3.2 Verify offset is advanced correctly after chunked read

## 4. API Rate Limiting

- [x] 4.1 Add `rate_limit.per_minute` (default: 10) and `rate_limit.daily_max` (default: 500) to the published config stub
- [x] 4.2 Create a `RateLimiter` helper (or inline logic in `AnalyzeThreat`) that checks and increments per-minute and daily cache counters before each Claude call
- [x] 4.3 In `AnalyzeThreat::handle()`, check rate limits before invoking the Claude API; log a warning and return early if either limit is exceeded
- [x] 4.4 Ensure daily counter cache key includes the UTC date so it resets at midnight

## 5. Queued Mail

- [x] 5.1 In `ThreatAgent::toolSendAlert()`, change `Mail::to(...)->send(...)` to `Mail::to(...)->queue(...)`

## 6. IP Regex Tightening

- [x] 6.1 Update the IP extraction regex in `LogParser` (or wherever `extractIp` lives) to match only valid IPv4 octets (0–255) and exclude matches preceded by a letter or `/`
- [x] 6.2 Add a unit test covering: valid IP extracted, version string not extracted (e.g. `guzzlehttp/guzzle 7.0.1`)
