## Purpose

Manages the IP blocklist — storage, expiry, and lookup interface for blocked IP addresses.

## Requirements

### Requirement: IP blocklist storage
The system SHALL maintain an `ip_blocklist` table that records blocked IPs with reason, confidence, blocked_by, blocked_at, and expires_at.

#### Scenario: IP blocked
- **WHEN** a block is written via the `block_ip` tool
- **THEN** a row is inserted with the IP, reason, confidence, `blocked_by = 'agent'`, and `expires_at` calculated from the configured TTL

#### Scenario: Duplicate block updates expiry
- **WHEN** an IP is blocked that already exists in the table
- **THEN** the existing row is updated (expiry extended, reason updated) rather than a duplicate inserted

### Requirement: Block expiry
The system SHALL treat blocks as expired once `expires_at` is in the past and SHALL NOT enforce expired blocks.

#### Scenario: Expired block ignored
- **WHEN** an IP is checked against the blocklist and its `expires_at` < now
- **THEN** the check returns unblocked (row is not deleted but treated as inactive)

### Requirement: Block check interface
The system SHALL provide a method `IpBlocklist::isBlocked(string $ip): bool` for use by host-app middleware.

#### Scenario: Active block found
- **WHEN** `isBlocked` is called with a currently-blocked IP
- **THEN** it returns `true`

#### Scenario: No active block
- **WHEN** `isBlocked` is called with an IP not in the blocklist or with only expired blocks
- **THEN** it returns `false`
