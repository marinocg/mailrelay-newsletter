=== RelayPress ===
Contributors: relaypress
Tags: newsletter, mailrelay, turnstile, gdpr
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.11.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Newsletter subscription with Mailrelay API + Cloudflare Turnstile + consent logging.

== Description ==
- Mailrelay official API integration
- Cloudflare Turnstile
- GDPR consent checkbox + DB log + retention
- Neutral UX to prevent email enumeration

== External Services ==
= Mailrelay API =
This plugin connects to Mailrelay to create or update subscribers when a form is submitted. It only sends requests after an admin configures an API base URL and API token and a visitor submits the form. Data sent includes subscriber email, name, list/group IDs, and any form fields you configure for Mailrelay.

Terms of use: https://mailrelay.com/en/terms-of-use/
Privacy policy: https://mailrelay.com/en/privacy-policy/

= Cloudflare Turnstile (optional) =
If enabled, the plugin loads Cloudflare Turnstile on the frontend and sends the Turnstile response token (and the requester IP) to Cloudflare for verification. This only happens after an admin enables Turnstile and enters site/secret keys.
Configure Turnstile keys in Settings -> RelayPress -> Turnstile.

Turnstile privacy addendum: https://www.cloudflare.com/turnstile-privacy-policy/
Cloudflare privacy policy: https://www.cloudflare.com/privacypolicy/

== Installation ==
1. Upload the plugin ZIP.
2. Activate it.
3. Configure Settings → RelayPress.

== Changelog ==
= Unreleased =
- Pending

= 1.11.1 =
- Add a11y improvements to the logs table (caption + ARIA toggle state).


= 1.11.0 =
- Move the extensions registry, state, and admin UI into core.
- Convert Turnstile to a core extension with generic extension hooks.
- Add core extension tests and hook-based Turnstile verification.
- Move Turnstile settings into a dedicated admin page.


= 1.10.0 =
- Add a neutral subscription use case for programmatic subscribers and logs.
- Route form submissions through the new subscription use case.


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
