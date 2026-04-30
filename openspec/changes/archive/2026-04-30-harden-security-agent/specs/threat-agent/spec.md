## MODIFIED Requirements

### Requirement: Claude agent invocation with tool use
The system SHALL invoke the Claude API with a structured system prompt and the suspicious log batch, providing tools the agent can call to research and respond to the threat. The system SHALL check per-minute and daily rate limits before making any Claude API call and SHALL skip invocation (logging a warning) if either limit is reached.

#### Scenario: Agent invoked with log context
- **WHEN** `AnalyzeThreat` job executes and rate limits are not exceeded
- **THEN** the system sends the log batch to Claude with tools: `get_ip_history`, `get_recent_events`, `block_ip`, `send_alert`

#### Scenario: Agent skipped due to rate limit
- **WHEN** `AnalyzeThreat` job executes and the per-minute or daily rate limit is reached
- **THEN** the system logs a warning, does not call the Claude API, and returns without updating `security_events`

#### Scenario: Agent calls a tool
- **WHEN** Claude responds with a tool use block
- **THEN** the system executes the corresponding PHP method and returns the result to Claude in the next turn

#### Scenario: Agent completes reasoning
- **WHEN** Claude responds with a text completion (no further tool calls)
- **THEN** the system saves the agent's summary and confidence score to `lsa_security_events`

### Requirement: IP extraction accuracy
The system SHALL extract IP addresses from log lines using a regex that matches only valid IPv4 addresses (each octet 0–255) and SHALL NOT match version strings or other dotted-decimal patterns found in log lines.

#### Scenario: Valid IP extracted
- **WHEN** a log line contains a valid IPv4 address (e.g. `192.168.1.100`)
- **THEN** the IP is extracted correctly

#### Scenario: Version string not extracted
- **WHEN** a log line contains a package version string (e.g. `guzzlehttp/guzzle 7.0.1`)
- **THEN** no IP is extracted from that token
