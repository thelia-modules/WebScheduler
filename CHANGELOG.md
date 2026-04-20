# Changelog

All notable changes to this module are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this module adheres to [Semantic Versioning](https://semver.org/).

## [1.1.0] — 2026-04-20

### Changed
- **Breaking**: trigger URLs are now static (no `ts` param). `sig` is computed
  from `slug + secret` only, without a time window. Paste once in the hosting
  cron panel and forget. To invalidate a leaked URL, regenerate the secret.
- Dropped the `HMAC_TIME_WINDOW_SECONDS` constant.

### Why
- Shared hostings (Infomaniak, OVH mutualisé, ...) expect a stable cron URL.
  A 5-minute HMAC window made the URL expire before the first cron tick.
  The security trade-off is moved to the secret itself, which is the usual
  model for webhook-style endpoints.

## [1.0.0] — 2026-04-20

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
