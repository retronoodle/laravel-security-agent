## Why

The monitor command currently runs every minute by default, which is aggressive for most deployments and generates unnecessary AI API calls. Users should control polling frequency to balance security responsiveness against resource/cost tradeoffs.

## What Changes

- Add `SECURITY_SCHEDULE_FREQUENCY` environment variable to configure scheduler frequency
- Update `SecurityAgentServiceProvider` to read the env var and set schedule frequency dynamically
- Default remains `everyMinute` for backward compatibility
- The config file `security-agent.php` will also expose this setting

## Capabilities

### New Capabilities
- `schedule-frequency`: Allows operators to configure how often the log monitor command runs (e.g., everyTenMinutes, hourly). Controlled via `SECURITY_SCHEDULE_FREQUENCY` env var with config fallback.

### Modified Capabilities
- `log-monitor`: The schedule frequency is no longer hardcoded to "every minute" — it is now operator-configurable via the `SECURITY_SCHEDULE_FREQUENCY` environment variable, defaulting to `everyMinute`.

## Impact

- **SecurityAgentServiceProvider**: Scheduler registration now reads from config
- **config/security-agent.php**: New `schedule_frequency` key added
- **README.md**: Document the new env var