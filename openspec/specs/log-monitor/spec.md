## Purpose

Polls the Laravel log file on a schedule, detects suspicious patterns, and dispatches threat analysis jobs.

## Requirements

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

### Requirement: Suspicious pattern detection
The system SHALL match log lines against a configurable set of regex patterns covering common attack signatures before invoking any AI analysis.

#### Scenario: SQLi probe detected
- **WHEN** a log line contains patterns matching SQL injection attempts (e.g. `UNION SELECT`, `' OR '1'='1`)
- **THEN** the line is flagged with pattern type `sqli` and included in the threat batch

#### Scenario: Auth brute force detected
- **WHEN** 5 or more failed login entries appear from the same IP within the polling window
- **THEN** the batch is flagged with pattern type `auth_brute_force`

#### Scenario: 404 flood detected
- **WHEN** 10 or more 404 entries appear from the same IP within the polling window
- **THEN** the batch is flagged with pattern type `404_flood`

#### Scenario: Benign lines ignored
- **WHEN** log lines contain no matching patterns
- **THEN** no job is dispatched and the offset advances past those lines
