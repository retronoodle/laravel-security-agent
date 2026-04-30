## MODIFIED Requirements

### Requirement: IP blocklist storage
The system SHALL maintain an `lsa_ip_blocklist` table that records blocked IPs with reason, confidence, blocked_by, blocked_at, and expires_at.

#### Scenario: IP blocked
- **WHEN** a block is written via the `block_ip` tool
- **THEN** a row is inserted with the IP, reason, confidence, `blocked_by = 'agent'`, and `expires_at` calculated from the configured TTL

#### Scenario: Duplicate block updates expiry
- **WHEN** an IP is blocked that already exists in the table
- **THEN** the existing row is updated (expiry extended, reason updated) rather than a duplicate inserted
