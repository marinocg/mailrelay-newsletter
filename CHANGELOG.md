# Changelog

## Unreleased
- Restructure plugin code into domain-aware ports/adapters/use-cases for consistency.
- Refresh forms and settings admin UIs with tabbed panels, help content, and improved controls.
- Add Mailrelay group selectors with cached API data in forms and settings.
- Improve logs table mobile behavior and responsiveness.
- Expand translations and regenerate MO files.
- Add plugin header requirements (WP/PHP) metadata.
- Add tests for submit use case and logs table rendering.
- Harden group ID handling to prevent tampering while preserving defaults.
- Validate logged page URLs with same-host checks and safer fallbacks.
- Add tests for group ID handling and safe page URL logic.
- Move MR4WP admin pages to a top-level menu with separate Settings and Logs screens.
- Improve logs UI with search, per-page controls, pagination, and sortable columns.
- Align logs table styling with WP list tables, including column sizing.
- Improve client IP detection for proxy/Cloudflare setups.
- Add tests for admin menu registration, logs table rendering, and client IP parsing.

## 1.7.1
- Block form after submit and add ajax behaviour


## 1.7.0
-


## 1.6.2
- Test release with new workflow (no changes)


## 1.6.1
- Add elementor support and refactors.


## 1.6.0

## 1.5.0
- Initial scaffolding.

## 1.4.1
- Fix dbDelta schema updates and safe column introspection.
