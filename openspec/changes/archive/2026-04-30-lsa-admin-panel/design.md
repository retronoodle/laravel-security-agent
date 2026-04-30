## Context

The Laravel Security Agent is a Composer-installable package with no web UI. Admins currently configure it entirely via `config/lsa.php` and `.env`. Adding a minimal admin panel at `/lsa-admin` gives teams a quick view of security activity and a safe way to update AI settings without SSH access.

## Goals / Non-Goals

**Goals:**
- Protected dashboard at `/lsa-admin` showing stats and recent findings
- Model selector that writes the chosen model to `lsa.model` (config + env)
- API key input that appends/updates `LSA_ANTHROPIC_API_KEY` in `.env` safely
- Publishable Blade views and a single controller
- No additional dependencies beyond what the package already uses

**Non-Goals:**
- Full CRUD for security events or IP blocklist
- User authentication system (relies on a single configurable password or existing app middleware)
- Real-time updates (no WebSockets, no polling)
- Role-based access control

## Decisions

### 1. Route protection via simple password middleware (not app auth)

The package cannot assume the host app uses Laravel Sanctum, Jetstream, or any auth system. A single `lsa_admin_password` config value checked by a lightweight middleware is sufficient for MVP and avoids coupling to host-app auth.

**Alternatives considered:** Requiring `auth` middleware (too opinionated for a package), optional middleware name in config (added complexity not needed for MVP).

### 2. Env file writer: regex-replace existing key, append if absent

Writing to `.env` is the riskiest part of this change. The writer will:
1. Read the file contents
2. If the key exists, replace only that line using a safe regex (`/^KEY=.*/m`)
3. If absent, append `KEY=value` on a new line
4. Write atomically (write to temp file, rename)

Never truncate, never rewrite the whole file. The key name is always a known constant (`LSA_ANTHROPIC_API_KEY`, `LSA_MODEL`) — no user-controlled input reaches the file write path.

**Alternatives considered:** Using `vlucas/phpdotenv` writer (not a package dep, adds weight), modifying config at runtime only (doesn't persist across restarts).

### 3. Single AdminController with named routes

All panel routes are prefixed `/lsa-admin` and registered in the package service provider under a configurable prefix. One controller handles dashboard, settings GET, and settings POST. Keeps surface area small.

### 4. Stats sourced directly from DB (no caching for MVP)

Dashboard queries `lsa_security_events` and `lsa_ip_blocklist` directly. Query volume is low (count + limit 10 recent rows). Caching can be added later if needed.

## Risks / Trade-offs

- **Env file write race condition** → Mitigation: atomic write via temp file + rename; single-process assumption is fine for MVP
- **Admin panel exposed without HTTPS** → Mitigation: document that `lsa_admin_password` should be set; note in README that HTTPS is strongly recommended
- **Blade views published to host app** → If host app has conflicting view names, the `vendor/` namespace avoids collisions; views published to `resources/views/vendor/lsa/`
- **Model names hardcoded in view** → List sourced from config so it can be extended without a code change

## Migration Plan

1. Register new routes in service provider (guarded by feature flag config `lsa.admin.enabled`, defaults true)
2. Publish views via `vendor:publish --tag=lsa-views`
3. Set `lsa.admin.password` in config (defaults to `null` = disabled with a warning log)
4. No database migrations required

Rollback: set `lsa.admin.enabled = false` in config.

## Open Questions

- Should the admin panel be opt-in (disabled by default) or opt-out? — Defaulting to **enabled** with a logged warning if no password is set seems more discoverable for new installs.
