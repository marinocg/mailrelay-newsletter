# RelayPress

RelayPress is a WordPress plugin that adds a Mailrelay newsletter form with Cloudflare Turnstile, GDPR consent, double opt-in support, and audit logs.

## Features
- Mailrelay API integration (active or inactive subscribers).
- Cloudflare Turnstile protection.
- GDPR consent checkbox and optional log retention.
- Neutral success message to avoid email enumeration.
- Shortcode, WordPress widget, and Elementor widget.
- Logs table with retention and manual purge.

## Requirements
- PHP 8.1+
- WordPress 6.0+
- Mailrelay account with API token
- Cloudflare Turnstile keys (optional but recommended)

## Installation (single site)
1. Download the ZIP from a GitHub Release.
2. WordPress -> Plugins -> Add New -> Upload Plugin -> Activate.
3. Settings -> RelayPress -> configure API and Turnstile.

## Usage
Shortcode:
```
[uve_mailrelay_newsletter]
```

Widget:
- Appearance -> Widgets -> "RelayPress"

Elementor:
- Search for "RelayPress" in the widget panel.

## Theme template override
You can override the frontend form by adding a template file in your theme:
- `your-theme/uve-mr-newsletter/form.php`
- or `your-theme/mr4wp/form.php`

The plugin will load the first matching file and fall back to its own template at `templates/form.php`.

## Configuration
Open Settings -> RelayPress and set:
- Mailrelay API base URL and token.
- Group IDs and subscriber status (active or inactive).
- Turnstile site and secret keys.
- Text labels and GDPR consent text.
- Log retention and rate limits.
- Enable AJAX submissions (optional).

## Logs and GDPR
Logs are stored in a dedicated table. You can:
- Enable or disable log storage.
- Hash IPs instead of storing raw IPs.
- Purge logs automatically after the retention window.
- Purge logs manually from the settings page.

## Local development
Install dev tools:
```
composer install
```

Create local PHPUnit config:
```
cp phpunit.xml.dist phpunit.xml
```

Run checks:
```
composer run phpcs
composer run phpstan
vendor/bin/phpunit
```

## Build a local ZIP for testing
CI compiles MO files during release. To package locally, make sure you compile translations and then zip the plugin folder.

Compile translations (requires `msgfmt`):
```
msgfmt languages/uve-mailrelay-newsletter-es_ES.po -o languages/uve-mailrelay-newsletter-es_ES.mo
msgfmt languages/uve-mailrelay-newsletter-en_US.po -o languages/uve-mailrelay-newsletter-en_US.mo
```

Create a ZIP from the plugin root (PowerShell example):
```
$zip = "relaypress.zip"
if (Test-Path $zip) { Remove-Item $zip }
Compress-Archive -Path * -DestinationPath $zip
```

Then upload `relaypress.zip` in WordPress -> Plugins -> Add New -> Upload Plugin.

## Releases (GitFlow)
Branches:
- `main`: production
- `develop`: integration
- `feature/*`: development
- `release/vX.Y.Z`: release prep
- `hotfix/vX.Y.Z`: urgent fixes

To cut a release:
1. Merge feature work into `develop`.
2. Run the "Release (GitFlow)" workflow in GitHub Actions.
3. The workflow bumps versions, runs checks, opens PRs, tags `vX.Y.Z`, and creates a ZIP.

## Translations
Translation files live in `languages/`.
To update translations, regenerate the POT and build PO/MO files as needed.
