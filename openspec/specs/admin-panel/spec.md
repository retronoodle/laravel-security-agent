## Purpose

Provides a password-protected web UI for monitoring security events and configuring the Laravel Security Agent at runtime.

## Requirements

### Requirement: Dashboard stats display
The system SHALL display a dashboard at `/lsa-admin` showing: total security events, total blocked IPs, and the 10 most recent security events (IP, pattern type, confidence, timestamp).

#### Scenario: Dashboard loads with data
- **WHEN** an authenticated admin visits `/lsa-admin`
- **THEN** the system displays event counts and a recent events table sourced from `lsa_security_events` and `lsa_ip_blocklist`

#### Scenario: Dashboard loads with no data
- **WHEN** an authenticated admin visits `/lsa-admin` and no events exist
- **THEN** the system displays zero counts and an empty state message

### Requirement: Admin panel access protection
The system SHALL protect all `/lsa-admin` routes with a password check. If no password is configured, the system SHALL log a warning and deny access.

#### Scenario: Correct password grants access
- **WHEN** an admin submits the correct password via the login form
- **THEN** the system sets a session flag and redirects to the dashboard

#### Scenario: Incorrect password denies access
- **WHEN** an admin submits an incorrect password
- **THEN** the system returns to the login form with an error message

#### Scenario: No password configured
- **WHEN** `lsa.admin.password` is null and any `/lsa-admin` route is visited
- **THEN** the system logs a warning and returns a 403 response

### Requirement: Model selector
The system SHALL display the currently configured Claude model and allow the admin to select a different model from a list defined in config. On save, the system SHALL update `LSA_MODEL` in the `.env` file.

#### Scenario: Admin changes model
- **WHEN** an authenticated admin selects a model from the dropdown and submits
- **THEN** the system updates `LSA_MODEL` in `.env` and redirects back with a success message

#### Scenario: Current model shown on load
- **WHEN** an authenticated admin visits the settings page
- **THEN** the currently configured model is pre-selected in the dropdown

### Requirement: API key configuration
The system SHALL provide a form field for the Anthropic API key. On submit, the system SHALL write the value to `LSA_ANTHROPIC_API_KEY` in `.env` using an atomic append-or-replace strategy. The key value SHALL NOT be displayed after saving (masked).

#### Scenario: Admin sets API key
- **WHEN** an authenticated admin enters an API key and submits
- **THEN** the system writes `LSA_ANTHROPIC_API_KEY=<value>` to `.env` (creating or updating the line) and redirects with a success message

#### Scenario: Existing key shown as masked
- **WHEN** an authenticated admin visits the settings page and a key is already set
- **THEN** the form displays the key as masked (e.g. `sk-ant-****`) not the full value

#### Scenario: Empty key submission ignored
- **WHEN** an authenticated admin submits the settings form with an empty API key field
- **THEN** the system does not modify `LSA_ANTHROPIC_API_KEY` in `.env`

### Requirement: Env file writer safety
The system SHALL write to `.env` atomically: write to a temporary file then rename it. It SHALL only modify lines matching the target key name (a known constant). It SHALL never truncate or rewrite the entire file.

#### Scenario: Key updated in place
- **WHEN** the target key already exists in `.env`
- **THEN** only that line is replaced; all other lines are unchanged

#### Scenario: Key appended when absent
- **WHEN** the target key does not exist in `.env`
- **THEN** the key=value pair is appended on a new line at the end of the file

#### Scenario: Atomic write on failure
- **WHEN** a write error occurs during the temp file write
- **THEN** the original `.env` is left unchanged
