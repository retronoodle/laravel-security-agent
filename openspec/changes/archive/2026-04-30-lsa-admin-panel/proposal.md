## Why

The Laravel Security Agent lacks a visual interface for administrators to monitor security activity, review recent findings, and configure AI settings without touching config files or environment variables directly. An admin panel makes the package usable and configurable for teams who don't want to manage settings manually.

## What Changes

- New route group at `/lsa-admin` with middleware-protected access
- Dashboard view showing key stats (events detected, IPs blocked, recent threats)
- Recent findings feed showing latest security events and threat analysis
- Model selector allowing admins to choose the active Anthropic Claude model
- API key configuration form that securely writes the key to `.env`

## Capabilities

### New Capabilities

- `admin-panel`: Web UI at `/lsa-admin` with stats dashboard, recent security events feed, model selector, and API key configuration

### Modified Capabilities

- `threat-agent`: Expose currently configured model name so the admin panel can read and update it

## Impact

- New Blade views and a controller published via `vendor:publish`
- New route registration in the package service provider
- Config additions: admin panel path, access password/middleware
- Env file writer utility (careful handling — append/update only, never truncate)
- Reads from `lsa_security_events` and `lsa_ip_blocklist` tables for stats
