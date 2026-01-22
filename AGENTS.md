# agents.md

This repository contains **MR4WP** (`relaypress-newsletter`), a WordPress plugin that provides:
- Newsletter subscription form (shortcode, widget, Elementor widget)
- Mailrelay API integration
- Optional Cloudflare Turnstile verification
- GDPR consent checkbox + DB logs + retention/purge
- Neutral UX to prevent email enumeration
- Multi-form support via a CPT + admin CRUD

If you are an automated coding agent (Copilot/ChatGPT/Cursor/etc.), follow these rules.

---

## Non-negotiable goals

### 1) Security-first behavior
- **Never introduce email enumeration**:
  - Frontend responses must remain neutral and must not reveal whether an email exists or is already subscribed.
- All state-changing flows must keep:
  - **Nonce verification** (`RelayPress_Newsletter::NONCE`)
  - **Capability checks** for admin actions
  - Input sanitization + output escaping everywhere.
- Keep existing abuse protections intact:
  - Honeypot field (`relaypress_hp`)
  - Rate limiting (transients-based)
  - Turnstile verification when enabled

### 2) Privacy / GDPR friendly by default
- Consent logs are stored in a dedicated DB table (`RelayPress_Logs`).
- Do not add new stored personal data unless absolutely necessary.
- Preserve retention + purge mechanics (scheduled purge).

### 3) WordPress compatibility & minimal dependencies
- Runtime targets (from plugin headers/readme):
  - WordPress **>= 6.0**
  - PHP **>= 8.0**
- Dev/testing may require newer PHP (e.g., PHPUnit 11 typically implies PHP 8.1+).
- No heavy frameworks; keep it “classic WP plugin” friendly.

### 4) Small, reviewable diffs
- Keep PRs narrowly scoped.
- Don’t reformat unrelated files.
- Match existing patterns (class naming, file layout, hooks, UI conventions).

---

## Repository layout (where things live)

### Root
- `class-relaypress-newsletter.php` — main plugin bootstrap, constants, hooks, activation.
- `uninstall.php` — uninstall behavior.
- `templates/form.php` — frontend form template (overrideable by theme).
- `languages/` — translations (`.po/.mo/.pot`).
- `assets/` — CSS (admin logs styling, etc.).
- `build-plugin-zip.sh` — builds a distributable ZIP (compiles translations, excludes dev files).

### `includes/` (hexagonal-ish)
- `includes/domain/` — domain objects (e.g., `RelayPress_Form`, `RelayPress_Form_Config`).
- `includes/ports/` — interfaces for adapters (repositories, rate limiter, turnstile verifier).
- `includes/adapters/` — WP implementations (wpdb/options/transients/wp_remote_request).
- `includes/use-cases/` — application logic (submit flow, form use cases).
- `includes/admin/` — admin UI (forms CRUD, tables).
- `includes/class-relaypress-container.php` — service container wiring.

Agent rule: when adding new behavior, prefer:
**domain → port → adapter → use-case**, and wire it via `RelayPress_Container`.

---

## Public APIs you must not break

### Shortcode
- `relaypress_newsletter`

### Submission endpoints
- `admin-post.php` actions:
  - `relaypress_subscribe` (priv + nopriv)
- AJAX actions:
  - `relaypress_subscribe_ajax` (priv + nopriv)

### Theme template overrides
Frontend template resolution allows theme overrides via:
- `your-theme/relaypress-newsletter/form.php`
- `your-theme/mr4wp/form.php`
Fallback:
- plugin `templates/form.php`

Do not change these paths without a backwards-compatible migration plan.

### Forms storage (CPT + meta)
- Post type: `mr4wp_form` (see `RelayPress_Form::POST_TYPE`)
- Meta keys:
  - `_mr4wp_form_config`
  - `_mr4wp_form_version`

Avoid changing the schema unless you also include a safe migration.

---

## Coding standards & conventions

- Classic WordPress style (no namespaces).
- Class naming: `RelayPress_*` / `RelayPress_Newsletter`.
- Always:
  - sanitize on input (`sanitize_text_field`, `absint`, `sanitize_email`, etc.)
  - escape on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`, etc.)
- SQL must use `$wpdb->prepare()` for parameterized queries.
- Prefer existing utilities in `RelayPress_Utils`.
- Keep text mostly ASCII unless the file already contains Unicode.

---

## Tests & quality gates (required)

**Rule:** Any new feature or bugfix must include **new/updated tests**.

- Add/adjust tests under `tests/` (prefer regression tests for the exact change).
- If a change is genuinely hard to test, include **manual verification steps** in the PR description,
  but still try to add at least one automated test.

### Before opening a PR, run all checks locally
This repo keeps dev tooling (composer, configs) in the repo, but the release ZIP excludes them.
So in a dev checkout:

```bash
composer install
````

Then run the full verification suite (use whichever form exists in the repo):

**Option A: composer scripts (preferred if present)**

```bash
composer run phpcs
composer run phpstan
composer run test
```

**Option B: direct binaries**

```bash
vendor/bin/phpcs -p
vendor/bin/phpstan analyse
vendor/bin/phpunit --configuration phpunit.xml
```

**PRs must be PHPCS/PHPStan clean and PHPUnit must pass.**

---

## Safe change guidelines (things agents must NOT do)

* Do not add telemetry, tracking, or unrelated external calls.
* Do not store new personal data by default.
* Do not change shortcode/action names, CPT keys, template override paths, or option keys without a migration plan.
* Do not weaken nonce/capability checks, sanitization/escaping, rate limiting, honeypot, or Turnstile logic.
* Do not add heavy dependencies or require a build step for runtime.

---

## Build & packaging (release ZIP)

Use:

```bash
./build-plugin-zip.sh
```

Notes:

* Requires `msgfmt` (to compile `.po` → `.mo`) and `zip`.
* Outputs `dist/relaypress-newsletter.zip`.
* Excludes dev files like `vendor/`, `composer.*`, `phpunit/phpstan/phpcs` configs, etc.
  (Do not change exclusions casually.)

---

## Branching expectations

* Work from `develop` (or the current default branch) unless the maintainer specifies otherwise.
* Use feature branches; keep PRs focused and easy to review.

---

## PR checklist (agents)

* [ ] Added/updated tests for the change (`tests/`)
* [ ] Added translations for all UI visible strings
* [ ] `vendor/bin/phpunit --configuration phpunit.xml` passes
* [ ] `vendor/bin/phpcs -p` passes
* [ ] `vendor/bin/phpstan analyse` passes
* [ ] No email enumeration leaks (frontend responses remain neutral)
* [ ] Nonces + capability checks for all writes
* [ ] Inputs sanitized, outputs escaped
* [ ] Documented any behavior/config changes (and added manual steps if needed)

## PR review note

- For PR review context, you can run `gh pr view <pr-url> --json reviews`.
- To inspect review threads and comments, prefer `gh pr-review threads list --pr <num> --repo <owner/repo>` and `gh api repos/<owner>/<repo>/pulls/<num>/comments` (since `gh-pr-review` doesn’t show bodies).

End.
