## Purpose

Handles outbound alert notifications to administrators when the AI agent identifies a threat requiring human review.

## Requirements

### Requirement: Admin email alert on escalation
The system SHALL send a `SecurityAlert` Mailable to all configured admin addresses when the agent determines a threat warrants human review (below auto-block threshold).

#### Scenario: Alert email sent
- **WHEN** the agent calls the `send_alert` tool
- **THEN** the system sends a `SecurityAlert` email to all addresses in `security-agent.admin_emails` config

#### Scenario: No admin emails configured
- **WHEN** `admin_emails` is empty
- **THEN** the system logs a warning and skips sending without throwing an exception

### Requirement: Alert email content
The alert email SHALL include the source IP, pattern type, agent summary, confidence score, and timestamp of detection.

#### Scenario: Email renders correctly
- **WHEN** the `SecurityAlert` mailable is rendered
- **THEN** it displays IP, pattern type, confidence score (as percentage), agent summary, and detection time
