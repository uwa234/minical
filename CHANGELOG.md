# Changelog

All notable changes to Veurion PMS are documented here. Versions follow [Semantic Versioning](https://semver.org/).

## [0.1.1] - 2026-10-31

### Security
- nginx: the web root is the project directory, so `.git`, `.env*`, database dumps, archives, logs, internal directories (`docker`, `vendor`, `tests`, `public/application`, `public/system`, `api/application`, ...) and root config files are now denied. Static images, CSS, JS and fonts under `public/application` (extension logos) are still served.

## [0.1.0] - 2026-10-31

Security hardening release. Requires `ENCRYPTION_KEY` in `.env` (see `.env.example`); setting it logs all users out once.

### Security
- API: every key is bound to its companies (`key_x_company`). Requests for another company are rejected, requests without `company_id` are scoped to the key's company, and unlinked keys are refused unless listed in `API_PLATFORM_KEYS`.
- API: booking payments and booking status updates now check the booking belongs to the company.
- API: fixed SQL injection in API key lookups.
- CSRF protection enabled: cross-site POSTs are rejected by Origin/Referer check, with exclusions for webhooks, payment gateway returns and the booking engine (`csrf_exclude_routes`, `CSRF_TRUSTED_ORIGINS`).
- Sessions: cookies are HMAC-signed, HttpOnly, Secure over HTTPS, use random session IDs, last 12 hours and rotate hourly.
- Encryption key moved to `ENCRYPTION_KEY`; platform secrets encrypted with the old default key are still readable.
- `?dev_mode` debugging (profiler, card and customer dumps) now requires a `DEVMODE_PASS` of 16+ characters; previously an empty value enabled it.
- Customer JSON endpoint checks customer ownership and masks card numbers.
- Removed default REST login, stopped tracking log files, ignored `.env.*` and database dumps.

## [0.0.1] - 2026-10-31

Baseline release of Veurion PMS (miniCal fork) as running in production before the security hardening pass.

- Veurion SaaS platform: Paystack subscription billing, trial registration, plan gating and platform admin.
- Hotel operations: booking calendar, room exchange, night audit, revenue management, invoice PDFs.
- Revenue AI recommendations and the redesigned rate plan / rates editor (daily and bulk tabs).
- POS room-charge API with idempotent external charge posting for the Odoo restaurant POS.
- Login attempt throttling (migration 031).
