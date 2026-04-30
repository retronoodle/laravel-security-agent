## 1. Package Scaffold

- [x] 1.1 Create `composer.json` with package name, autoload PSR-4 (`YourVendor\SecurityAgent\`), and Laravel auto-discovery for the service provider
- [x] 1.2 Create `src/SecurityAgentServiceProvider.php` — registers config, migrations, views, and the scheduler command
- [x] 1.3 Create `config/security-agent.php` with keys: `log_path`, `schedule_frequency`, `confidence_threshold`, `block_ttl_minutes`, `admin_emails`, `anthropic_api_key`, `anthropic_model`

## 2. Database Migrations

- [x] 2.1 Create migration `create_security_events_table` — columns: `id`, `ip_address`, `pattern_type`, `raw_excerpt` (text), `agent_summary` (text nullable), `confidence` (float nullable), `outcome` (enum: pending/blocked/alerted/ignored, default pending), `created_at`, `updated_at`
- [x] 2.2 Create migration `create_ip_blocklist_table` — columns: `id`, `ip_address` (unique), `reason` (text), `confidence` (float), `blocked_by` (string, default agent), `blocked_at` (timestamp), `expires_at` (timestamp), `created_at`, `updated_at`

## 3. Log Monitor

- [x] 3.1 Create `src/Services/LogParser.php` — reads new lines from log file using cached byte offset; returns array of raw lines
- [x] 3.2 Add pattern matching to `LogParser` — regex rules for `sqli`, `auth_brute_force`, `404_flood`; groups matches by IP and pattern type
- [x] 3.3 Create `src/Console/Commands/MonitorLogs.php` — calls `LogParser`, dispatches `AnalyzeThreat` per suspicious batch, updates offset in cache
- [x] 3.4 Register `MonitorLogs` in service provider scheduler at configured frequency

## 4. Threat Agent

- [x] 4.1 Create `src/Services/IpBlocklist.php` — `isBlocked(string $ip): bool`, `block(string $ip, string $reason, float $confidence, int $ttlMinutes): void` (upsert)
- [x] 4.2 Create `src/Services/ThreatAgent.php` — builds tool definitions for Claude, runs the tool-use loop (max 10 turns), returns final summary + confidence
- [x] 4.3 Implement `get_ip_history` tool handler in `ThreatAgent` — queries `security_events` for IP count + last seen
- [x] 4.4 Implement `get_recent_events` tool handler in `ThreatAgent` — returns last N events across all IPs
- [x] 4.5 Implement `block_ip` tool handler in `ThreatAgent` — enforces confidence threshold, calls `IpBlocklist::block()` or returns rejection
- [x] 4.6 Implement `send_alert` tool handler in `ThreatAgent` — dispatches `SecurityAlert` mailable

## 5. Job & Mail

- [x] 5.1 Create `src/Jobs/AnalyzeThreat.php` — creates `security_events` row (pending), calls `ThreatAgent`, updates row with summary/confidence/outcome
- [x] 5.2 Create `src/Mail/SecurityAlert.php` — Mailable with IP, pattern type, confidence, summary, timestamp
- [x] 5.3 Create `resources/views/emails/security-alert.blade.php` — plain readable email with all alert fields

## 6. Wiring & Publish Tags

- [x] 6.1 Register publish tags in service provider: `security-agent-config`, `security-agent-migrations`, `security-agent-views`
- [x] 6.2 Write `README.md` install steps: composer require → vendor:publish → migrate → .env keys
