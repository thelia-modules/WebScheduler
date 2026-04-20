# Changelog

All notable changes to this module are documented here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this module adheres to [Semantic Versioning](https://semver.org/).

## [1.1.2] — 2026-04-20

### Changed
- Task form wording: the secret box no longer says "store it safely"
  (misleading for web-cron use). It now clarifies that the secret is
  already embedded in the URL's signature and only needs to be saved for
  external signing integrations.
- Trigger URL hint: removed the obsolete "new signature at each display"
  note (URLs are stable since 1.1.0) and added the "leave the password
  field unchecked" reminder for Infomaniak/OVH panels.

## [1.1.1] — 2026-04-20

### Fixed
- `CommandRegistry::all()` no longer crashes the admin page when another
  module's command has a broken constructor (e.g. depends on
  `allow_url_fopen` on a restricted hosting). Failing commands are logged
  and skipped, the admin page keeps rendering.

### Changed
- Diagnostic page rewritten: capability details rendered as readable
  key/value pairs instead of JSON. The "elected" strategy (the one picked
  by `Auto`) is highlighted in green.

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
