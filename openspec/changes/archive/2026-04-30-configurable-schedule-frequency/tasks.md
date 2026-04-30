## 1. Config

- [x] 1.1 Verify `config/security-agent.php` exposes `schedule_frequency` from env (`SECURITY_SCHEDULE_FREQUENCY`, default `everyMinute`) — already done

## 2. Service Provider

- [x] 2.1 Add whitelist of valid scheduler frequencies in `SecurityAgentServiceProvider`
- [x] 2.2 Validate `schedule_frequency` against whitelist before scheduling
- [x] 2.3 Log warning and fall back to `everyMinute` if value is invalid

## 3. Tests

- [x] 3.1 Unit test: valid frequency → scheduler uses configured value
- [x] 3.2 Unit test: invalid frequency → logs warning and falls back to `everyMinute`
- [x] 3.3 Unit test: no frequency set → defaults to `everyMinute`

## 4. Documentation

- [x] 4.1 Update README to document `SECURITY_SCHEDULE_FREQUENCY` env var and allowed values