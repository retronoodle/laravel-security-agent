## 1. Config & Routes

- [x] 1.1 Add `admin` key to `lsa.php` config with `enabled`, `password`, `path`, and `available_models` entries
- [x] 1.2 Add `model` key to `lsa.php` config (default: `claude-sonnet-4-6`)
- [x] 1.3 Register `/lsa-admin` route group in the service provider, guarded by `lsa.admin.enabled`

## 2. Middleware & Auth

- [x] 2.1 Create `AdminAuthMiddleware` that checks `lsa.admin.password` and session flag; returns 403 if no password configured (with warning log)
- [x] 2.2 Register `AdminAuthMiddleware` in the route group
- [x] 2.3 Create login route (`GET /lsa-admin/login`, `POST /lsa-admin/login`) outside the auth middleware

## 3. AdminController

- [x] 3.1 Create `AdminController` with `showLogin`, `login`, `dashboard`, `showSettings`, `saveSettings` methods
- [x] 3.2 Implement `dashboard`: query event count, blocked IP count, 10 most recent `lsa_security_events` rows
- [x] 3.3 Implement `showSettings`: pass current model (from config) and masked API key to view
- [x] 3.4 Implement `saveSettings`: validate input, call `EnvWriter` for model and API key if provided, redirect with success

## 4. EnvWriter Utility

- [x] 4.1 Create `EnvWriter` class with a `set(string $key, string $value): void` method
- [x] 4.2 Implement atomic write: read `.env`, replace matching line or append, write to temp file, rename to `.env`
- [x] 4.3 Ensure only known constant key names (`LSA_MODEL`, `LSA_ANTHROPIC_API_KEY`) are accepted; throw on unknown keys

## 5. Blade Views

- [x] 5.1 Create `resources/views/lsa/admin/login.blade.php` (password form)
- [x] 5.2 Create `resources/views/lsa/admin/dashboard.blade.php` (stats + recent events table)
- [x] 5.3 Create `resources/views/lsa/admin/settings.blade.php` (model dropdown + masked API key field)
- [x] 5.4 Create `resources/views/lsa/admin/layout.blade.php` (shared nav/wrapper)

## 6. Threat Agent: Config-driven Model

- [x] 6.1 Update `AnalyzeThreat` job (or the Claude HTTP call) to read model from `config('lsa.model')` instead of a hardcoded string

## 7. Publishing & Registration

- [x] 7.1 Register views in the service provider under the `lsa` namespace
- [x] 7.2 Add views to `vendor:publish` tags (`lsa-views`)
- [x] 7.3 Update package config publish tag to include the new `admin` and `model` keys

## 8. Tests

- [x] 8.1 Unit test `EnvWriter`: key updated in place, key appended when absent, original unchanged on write failure
- [x] 8.2 Feature test: unauthenticated access redirects to login, correct password grants session, wrong password rejected
- [x] 8.3 Feature test: dashboard returns 200 with event/IP counts
- [x] 8.4 Feature test: settings save updates `.env` via `EnvWriter` (mock writer)
