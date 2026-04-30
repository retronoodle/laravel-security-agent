## Context

The `SecurityAgentServiceProvider` currently registers the scheduler with a hardcoded `everyMinute()` frequency:

```php
Schedule::command('security-agent:monitor')->everyMinute();
```

This frequency should be operator-configurable via an environment variable, defaulting to `everyMinute` for backward compatibility.

## Goals / Non-Goals

**Goals:**
- Allow operators to configure monitor polling frequency via `SECURITY_SCHEDULE_FREQUENCY` env var
- Default to `everyMinute` to preserve existing behavior
- Support Laravel scheduler frequencies: `everyMinute`, `everyFiveMinutes`, `everyTenMinutes`, `hourly`

**Non-Goals:**
- Adding custom cron expressions or intervals not natively supported by Laravel's Scheduler
- Runtime frequency changes without deploy (env var requires restart)

## Decisions

**Decision 1: Env var `SECURITY_SCHEDULE_FREQUENCY`**

Store the frequency string in the env and read it in the service provider. Rationale: follows Laravel conventions (config caching, env-based deployment), keeps config file clean, no need to publish/override service provider.

**Decision 2: Validate at registration time**

Validate the frequency string against a whitelist of allowed Laravel scheduler methods. Invalid values trigger a log warning and fall back to `everyMinute`.

Allowed values (maps directly to the Laravel Scheduler method called on the event):
- `everyMinute`
- `everyFiveMinutes`
- `everyTenMinutes`
- `everyFifteenMinutes`
- `everyThirtyMinutes`
- `hourly`

**Decision 3: Config file as secondary fallback**

Expose `schedule_frequency` in `config/security-agent.php` so it can be set via Laravel's config system if env var is not set.

## Risks / Trade-offs

[Risk] Operator sets invalid frequency string → Mitigation: Whitelist validation with fallback to default
[Risk] Changing frequency after deploy requires env change + config cache clear → Mitigation: Document the requirement in README
[Note] Default of `everyMinute` is aggressive for most deployments but preserved for backward compatibility. A future change should consider updating the default (e.g. `everyFiveMinutes`) once the package has an established install base.