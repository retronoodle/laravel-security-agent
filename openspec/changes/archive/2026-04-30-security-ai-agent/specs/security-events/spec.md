## ADDED Requirements

### Requirement: Security event logging
The system SHALL record every detected threat event in a `security_events` table, including raw log context, agent analysis, outcome, and confidence score.

#### Scenario: Event created on detection
- **WHEN** `MonitorLogs` dispatches an `AnalyzeThreat` job
- **THEN** a `security_events` row is created with status `pending`, the source IP, pattern type, and raw log excerpt

#### Scenario: Event updated after agent completes
- **WHEN** the `ThreatAgent` finishes reasoning
- **THEN** the event row is updated with the agent summary, confidence score, outcome (`blocked` or `alerted`), and status `resolved`

### Requirement: Security events schema
The `security_events` table SHALL contain: `id`, `ip_address`, `pattern_type`, `raw_excerpt` (text), `agent_summary` (text, nullable), `confidence` (float, nullable), `outcome` (enum: pending/blocked/alerted/ignored), `created_at`, `updated_at`.

#### Scenario: Row structure valid
- **WHEN** a new event is inserted
- **THEN** all non-nullable columns are populated and `outcome` defaults to `pending`
