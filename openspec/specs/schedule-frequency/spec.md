## Purpose

Allows operators to configure the monitor polling frequency via an environment variable, defaulting to every minute when not set.

## Requirements

### Requirement: Configurable schedule frequency
The system SHALL allow operators to configure the monitor polling frequency via the `SECURITY_SCHEDULE_FREQUENCY` environment variable, defaulting to `everyMinute` when not set.

#### Scenario: Valid frequency configured
- **WHEN** `SECURITY_SCHEDULE_FREQUENCY` is set to a valid Laravel scheduler frequency (e.g., `everyTenMinutes`)
- **THEN** the scheduler runs the monitor command at the configured frequency

#### Scenario: Invalid frequency configured
- **WHEN** `SECURITY_SCHEDULE_FREQUENCY` is set to an invalid or unrecognized value
- **THEN** the system logs a warning and falls back to `everyMinute`

#### Scenario: No frequency configured
- **WHEN** `SECURITY_SCHEDULE_FREQUENCY` is not set
- **THEN** the system defaults to `everyMinute`
