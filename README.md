# WebScheduler — Thelia module

Trigger Symfony Console commands from signed HTTP URLs, with an execution
strategy that adapts to the hosting's capabilities.

Designed for shared hostings (Infomaniak, OVH mutualisé, …) that only offer
web-based scheduled tasks and no real cron.

## How it works

The hosting's scheduler hits a signed URL on your Thelia site. The module
authenticates the request (HMAC-SHA256 signature with a short time window),
picks the best available execution strategy and runs the configured command.

Three strategies are available. The `auto` strategy (default) picks the best
one supported by the hosting — detected and cached at runtime.

| Strategy | Requires | Behaviour |
| --- | --- | --- |
| `cli_fork` | `proc_open` + a PHP CLI binary | Spawns `nohup php Thelia <cmd> &` detached from the HTTP worker. HTTP responds in milliseconds. Best choice for long-running syncs. |
| `fastcgi_finish` | `fastcgi_finish_request` | Responds to the client, flushes the PHP-FPM buffer, then runs the command in-process (via an external process if `proc_open` is allowed, in-process Thelia Application otherwise). |
| `sync` | none | Runs the command in the HTTP request and returns the output. Limited by PHP's `max_execution_time`. Last-resort fallback. |

## Features

- HMAC-signed trigger URLs with a 5-minute validity window
- Per-task opaque slug and secret (secret revealed once at creation)
- Per-task IP allowlist (CIDR), minimum interval rate limit, maximum runtime
- Concurrency lock (`symfony/lock`, file-based) — overlapping calls are skipped
- Execution history with status, exit code, strategy used and command output
- Back-office diagnostic panel — shows which capabilities the hosting provides
  and which strategies are supported
- Back-office CRUD for tasks, manual "trigger now" button, secret regeneration
- Any Symfony Console command registered in Thelia is schedulable (no need to
  write module-side glue)

## Install

```bash
composer require thelia/web-scheduler-module
```

Activate the module in the back-office → Modules.

## Quick start

1. Back-office → Tools → **Web Scheduler**
2. **Create a task** — pick a command (auto-completed from your registered
   Symfony commands), set optional arguments, choose `auto` strategy.
3. On save, the secret is revealed **once** — store it if you ever need to
   sign URLs outside the module.
4. Copy the **Trigger URL** from the task list (or from the task edit page).
5. Paste that URL into your hosting's scheduled-tasks panel.

### Infomaniak example

On Infomaniak's "Planifier une tâche" panel:

- URL: paste the full trigger URL (including `?ts=...&sig=...`)
- Password: leave unchecked — the HMAC signature replaces URL basic-auth

Infomaniak regenerates the call at each cron tick with the URL you provided.
The signature window is 5 minutes, which is largely enough for any cron hop.

If the URL's timestamp ever expires (for hostings that cache the URL for
longer than 5 minutes), regenerate it from the task edit page — the module
recomputes a fresh timestamp + signature every time the admin view is loaded.

## Security model

- Each task has a unique 32-hex slug embedded in the URL path.
- The URL is signed: `sig = HMAC-SHA256(slug|timestamp, secret)`.
- The timestamp must be within ±5 minutes of the server clock.
- Signature comparison is timing-safe (`hash_equals`).
- Command arguments are **frozen** per task — nothing from the query string
  influences the executed command. No RCE surface.
- Optional per-task IP allowlist (CIDR, one entry per line).
- Optional per-task rate limit (`min_interval_seconds`).
- Rejected requests always respond `202 Accepted` with a neutral body, to
  avoid leaking task existence.

## Dev notes

- Namespace: `WebScheduler\*` (PSR-4)
- Requires PHP 8.3+, Thelia 2.6+, Symfony 6.4 or 7.x
- Strategies are services tagged `webscheduler.execution_strategy`, loaded
  into `StrategyResolver` via `#[AutowireIterator]`
- Lock store: `FlockStore` rooted at `THELIA_CACHE_DIR/webscheduler_locks`
- Command capture: output is truncated to 64 KiB per execution

## License

LGPL-3.0-or-later
