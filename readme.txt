=== RelayPress ===
Contributors: relaypress
Tags: newsletter, mailrelay, turnstile, gdpr
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Newsletter subscription with Mailrelay API + Cloudflare Turnstile + consent logging.

== Description ==
- Mailrelay official API integration
- Cloudflare Turnstile
- GDPR consent checkbox + DB log + retention
- Neutral UX to prevent email enumeration

== Installation ==
1. Upload the plugin ZIP.
2. Activate it.
3. Configure Settings → RelayPress.

== Changelog ==
= 1.9.0 =
- Add a country selector field with ISO code validation and localized options.
- Sort country options by translated label and align select styling with text inputs.
- Extend submission handling and tests for the country field.
- Phone normalization

= 1.8.0 =
- Full restructure, forms management

= 1.7.1 =
- Block form after submit and add ajax behaviour

= 1.7.0 =
-

= 1.6.2 =
- Test release with new workflow (no changes)

= 1.6.1 =
- Add elementor support and refactors.

= 1.6.0 =

= 1.5.0 =

= 1.4.1 =
- Fix dbDelta schema updates and safe column introspection.
