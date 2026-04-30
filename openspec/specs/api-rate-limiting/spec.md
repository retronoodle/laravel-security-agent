## Purpose

Enforces configurable rate limits on Claude API calls to prevent runaway usage — both per-minute throttling and a daily cap.

## Requirements

### Requirement: Per-minute Claude API call throttle
The system SHALL enforce a configurable maximum number of Claude API calls per minute, tracked via a cache counter, and SHALL skip job processing (logging a warning) when the limit is reached.

#### Scenario: Under the per-minute limit
- **WHEN** the per-minute call counter is below `security-agent.rate_limit.per_minute`
- **THEN** the Claude API call proceeds and the counter is incremented

#### Scenario: Per-minute limit reached
- **WHEN** the per-minute call counter equals or exceeds `security-agent.rate_limit.per_minute`
- **THEN** the system skips the Claude call, logs a warning with the throttle reason, and returns without updating `security_events`

### Requirement: Daily Claude API call cap
The system SHALL enforce a configurable maximum number of Claude API calls per UTC calendar day, tracked via a cache counter that resets at midnight UTC, and SHALL skip job processing when the cap is reached.

#### Scenario: Under the daily cap
- **WHEN** the daily call counter is below `security-agent.rate_limit.daily_max`
- **THEN** the Claude API call proceeds and the daily counter is incremented

#### Scenario: Daily cap reached
- **WHEN** the daily call counter equals or exceeds `security-agent.rate_limit.daily_max`
- **THEN** the system skips all Claude calls for the remainder of the day and logs a single warning per invocation

### Requirement: Rate limit config keys
The published config SHALL include `rate_limit.per_minute` (default: 10) and `rate_limit.daily_max` (default: 500) with comments explaining their purpose.

#### Scenario: Config published with defaults
- **WHEN** a user runs `php artisan vendor:publish --tag=security-agent-config`
- **THEN** the published config file contains `rate_limit.per_minute` set to 10 and `rate_limit.daily_max` set to 500
