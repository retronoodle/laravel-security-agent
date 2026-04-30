## Context

The package is installable via `composer require` into any Laravel 8+ app. The MVP shipped with several gaps that surface as real operational problems: generic table names that collide with host-app tables, no guard on the scheduler hook, unbounded Claude API calls, a full-file read on cold start, synchronous mail inside a queued job, and an IP regex that matches version strings. All fixes are contained to the package internals — no host-app API changes.

## Goals / Non-Goals

**Goals:**
- Prefix all package-owned tables with `lsa_` to avoid collisions
- Guard the scheduler registration behind a config/key presence check
- Add a per-minute and daily Claude API call cap (configurable, default sensible)
- Stream the log file in chunks instead of a full read on first install
- Switch alert mail to queued dispatch
- Tighten IP regex to reject non-IP dotted strings

**Non-Goals:**
- Changing the outward API surface (config keys remain backward-compatible where possible; new keys are additive)
- Supporting queue drivers beyond sync for the MVP
- Migrating existing installs automatically (out of scope; upgrade note suffices)

## Decisions

### Table prefix: `lsa_`
Chosen over namespacing via config because a config-driven prefix creates runtime complexity (model `$table` would need to read config) and could silently break if the key is missing. A fixed prefix is boring and safe.

### Rate limiting via Laravel Cache
Per-minute and daily counters stored in the default cache store using atomic increments (`Cache::add` + `Cache::increment`). Chosen over a DB table (extra migration/query) or Redis-specific primitives (can't assume Redis). Downside: cache flush resets counters mid-day.

### Log streaming via `fread` with chunk size
`fread($handle, 8192)` in a loop from the stored offset replaces `file_get_contents` from byte 0. Chunk size is a constant (not config) — 8 KB is the typical FS block size and avoids micro-config creep. On first run the offset is 0 so we stream the whole file, but in small pieces — peak RAM is bounded.

### Scheduler guard: check both config key presence and non-empty string
`config('security-agent.anthropic_api_key')` or `env('ANTHROPIC_API_KEY')` — the guard only needs to be truthy. We avoid dispatching jobs that will immediately fail with an auth error.

### Queued mail: `Mail::to()->queue()`
Drop-in swap. Works with the sync queue driver (job runs inline) and is forward-compatible when the host switches to a real queue. No new dependency.

### IP regex tightening
Old: `/\b(\d{1,3}(?:\.\d{1,3}){3})\b/` — matches `7.0.1` in `guzzlehttp/guzzle 7.0.1` if preceded by a space.  
New: require each octet to be 0–255 and the match must not be preceded by a letter or `/`. Pattern: `/(?<![a-zA-Z\/])(\b(?:(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\.){3}(?:25[0-5]|2[0-4]\d|[01]?\d\d?)\b)/`  
Trade-off: slightly more expensive regex, negligible at log-line scale.

## Risks / Trade-offs

- **Cache-based rate limiting resets on flush** → document the limitation; future versions can use DB counters
- **Table rename breaks existing installs** → provide a clear upgrade note; no auto-migration (too risky to run destructive SQL automatically)
- **Chunk streaming on first run still processes entire file** → bounded RAM but the job may take longer on huge logs; acceptable for MVP
- **Scheduler guard means jobs never run if key is missing** → correct behavior; fail-safe over fail-open

## Migration Plan

1. New installs: run `php artisan migrate` as normal — new table names appear
2. Existing installs: manually rename tables (`ALTER TABLE security_events RENAME TO lsa_security_events` etc.) and re-publish config to pick up new rate limit keys
3. Rollback: table renames are reversible; rate limit and regex changes are stateless

## Open Questions

- Should the daily API cap be per-process or global (shared via cache)? Current design: global via shared cache store. Sufficient for single-server installs; multi-server would need a shared cache backend (Redis), which the host should configure.
