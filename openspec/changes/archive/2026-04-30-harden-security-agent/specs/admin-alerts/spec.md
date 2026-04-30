## MODIFIED Requirements

### Requirement: Admin email alert on escalation
The system SHALL send a `SecurityAlert` Mailable to all configured admin addresses when the agent determines a threat warrants human review (below auto-block threshold). The mail SHALL be dispatched via the queue (`Mail::to()->queue()`) rather than synchronously.

#### Scenario: Alert email queued
- **WHEN** the agent calls the `send_alert` tool
- **THEN** the system queues a `SecurityAlert` email to all addresses in `security-agent.admin_emails` config (does not block the queue worker)

#### Scenario: No admin emails configured
- **WHEN** `admin_emails` is empty
- **THEN** the system logs a warning and skips sending without throwing an exception
