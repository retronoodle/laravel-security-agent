## MODIFIED Requirements

### Requirement: Scheduled log polling
The system SHALL poll the Laravel log file on a configurable schedule (default: every minute) using a Laravel Scheduler command, reading only new lines since the last poll using a byte-offset stored in the Laravel cache. The scheduler registration SHALL only occur when `ANTHROPIC_API_KEY` (or `security-agent.anthropic_api_key` config) is set to a non-empty value.

#### Scenario: New suspicious lines detected
- **WHEN** the scheduler runs and new log lines match a suspicious pattern
- **THEN** the system dispatches an `AnalyzeThreat` job with the matched lines and extracted metadata (IP, timestamp, URI, pattern type)

#### Scenario: No new lines since last poll
- **WHEN** the scheduler runs and the log file has not grown since last offset
- **THEN** the system exits silently with no job dispatched

#### Scenario: Log file missing
- **WHEN** the scheduler runs and the configured log path does not exist
- **THEN** the system logs a warning and exits without throwing an exception

#### Scenario: Scheduler skipped when API key absent
- **WHEN** the service provider boots and `ANTHROPIC_API_KEY` is empty or not set
- **THEN** the scheduler command is NOT registered and no jobs are dispatched

### Requirement: Log file streaming
The system SHALL read new log content from the stored byte offset using chunked streaming (8 KB chunks) rather than loading the entire file into memory at once.

#### Scenario: Chunked read on incremental poll
- **WHEN** the scheduler runs and the log file has grown since last offset
- **THEN** the system reads from the offset to end-of-file in 8 KB chunks, accumulating only matched lines in memory

#### Scenario: First run on large log file
- **WHEN** the stored offset is 0 and the log file is larger than 8 KB
- **THEN** the system streams the file in chunks without loading it entirely into memory
