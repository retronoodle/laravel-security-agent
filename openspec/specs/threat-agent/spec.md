## Purpose

Orchestrates the Claude AI agent loop — invoking Claude with tool use to research and respond to detected threats.

## Requirements

### Requirement: Claude agent invocation with tool use
The system SHALL invoke the Claude API with a structured system prompt and the suspicious log batch, providing tools the agent can call to research and respond to the threat.

#### Scenario: Agent invoked with log context
- **WHEN** `AnalyzeThreat` job executes
- **THEN** the system sends the log batch to Claude with tools: `get_ip_history`, `get_recent_events`, `block_ip`, `send_alert`

#### Scenario: Agent calls a tool
- **WHEN** Claude responds with a tool use block
- **THEN** the system executes the corresponding PHP method and returns the result to Claude in the next turn

#### Scenario: Agent completes reasoning
- **WHEN** Claude responds with a text completion (no further tool calls)
- **THEN** the system saves the agent's summary and confidence score to `security_events`

### Requirement: Tool — get_ip_history
The system SHALL provide a tool that returns the count and most recent timestamps of prior security events for a given IP.

#### Scenario: Known offender lookup
- **WHEN** Claude calls `get_ip_history` with an IP address
- **THEN** the system returns the event count and last-seen timestamp from `security_events`

#### Scenario: Unknown IP lookup
- **WHEN** Claude calls `get_ip_history` with an IP that has no prior events
- **THEN** the system returns count 0 and null timestamp

### Requirement: Tool — get_recent_events
The system SHALL provide a tool that returns the last N security events across all IPs to give the agent campaign-level context.

#### Scenario: Recent events retrieved
- **WHEN** Claude calls `get_recent_events` with a limit
- **THEN** the system returns up to that many recent `security_events` rows (IP, pattern type, timestamp, confidence)

### Requirement: Tool — block_ip
The system SHALL provide a tool that writes an IP to the `ip_blocklist` table with a reason, confidence score, and expiry.

#### Scenario: High-confidence auto-block
- **WHEN** Claude calls `block_ip` with confidence >= configured threshold
- **THEN** the system inserts the IP into `ip_blocklist` with the provided reason and TTL, and records the outcome in `security_events`

#### Scenario: Block rejected below threshold
- **WHEN** Claude calls `block_ip` with confidence below configured threshold
- **THEN** the system rejects the block, returns an error result to Claude, and Claude falls back to sending an alert

### Requirement: Tool — send_alert
The system SHALL provide a tool that dispatches a `SecurityAlert` email to configured admin addresses.

#### Scenario: Alert sent for ambiguous threat
- **WHEN** Claude calls `send_alert` with a summary and confidence score
- **THEN** the system sends the alert email and records the outcome in `security_events`

### Requirement: Agent turn limit
The system SHALL limit the agent to a maximum of 10 tool-call turns per invocation to prevent runaway API usage.

#### Scenario: Turn limit reached
- **WHEN** the agent has made 10 tool calls without completing
- **THEN** the system forces completion, logs a warning, and saves a partial summary
