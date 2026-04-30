## MODIFIED Requirements

### Requirement: Scheduled log polling
The system SHALL poll the Laravel log file on a configurable schedule (default: every minute) using a Laravel Scheduler command, reading only new lines since the last poll using a byte-offset stored in the Laravel cache.

#### Scenario: New suspicious lines detected
- **WHEN** the scheduler runs and new log lines match a suspicious pattern
- **THEN** the system dispatches an `AnalyzeThreat` job with the matched lines and extracted metadata (IP, timestamp, URI, pattern type)

#### Scenario: No new lines since last poll
- **WHEN** the scheduler runs and the log file has not grown since last offset
- **THEN** the system exits silently with no job dispatched

#### Scenario: Log file missing
- **WHEN** the scheduler runs and the configured log path does not exist
- **THEN** the system logs a warning and exits without throwing an exception