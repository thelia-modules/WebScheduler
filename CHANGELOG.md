# Changelog

All notable changes to this module are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this module adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] — unreleased

### Added
- Module skeleton with Thelia 2.6+ / PHP 8.3+ baseline
- Three execution strategies: `cli_fork`, `fastcgi_finish`, `sync`
- Capability prober with on-disk cache (TTL 1h) and admin refresh
- HMAC-signed trigger endpoint (`/web-scheduler/run/{slug}`)
- Per-task IP allowlist, rate limit and maximum runtime
- Concurrency lock (`FlockStore`) preventing overlapping executions
- Back-office CRUD, manual trigger, secret regeneration
- Execution history with output capture (truncated to 64 KiB)
- Diagnostic panel (hosting capabilities + supported strategies)
- Propel schema with three tables:
  `web_scheduler_task`, `web_scheduler_execution`, `web_scheduler_capability`
