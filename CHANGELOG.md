# Changelog

All notable changes to Veurion PMS are documented here. Versions follow [Semantic Versioning](https://semver.org/).

## [0.0.1] - 2026-10-31

Baseline release of Veurion PMS (miniCal fork) as running in production before the security hardening pass.

- Veurion SaaS platform: Paystack subscription billing, trial registration, plan gating and platform admin.
- Hotel operations: booking calendar, room exchange, night audit, revenue management, invoice PDFs.
- Revenue AI recommendations and the redesigned rate plan / rates editor (daily and bulk tabs).
- POS room-charge API with idempotent external charge posting for the Odoo restaurant POS.
- Login attempt throttling (migration 031).
