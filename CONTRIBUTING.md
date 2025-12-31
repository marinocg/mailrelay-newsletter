# Contributing

Thanks for considering a contribution. This plugin targets WordPress admin/front‑end behavior, so changes should be small, tested, and PHPCS/PHPStan clean.

## How to work
- Create a branch from `develop` (or the current default branch).
- Keep changes scoped to one purpose.
- Use ASCII text unless the file already contains Unicode.
- Avoid reformatting unrelated code.

## Development setup
- PHP >= 8.1 (tests use PHPUnit 11).
- Install dependencies: `composer install`

## Quality checks
Run these before opening a PR and before submitting it:

```bash
vendor/bin/phpunit --configuration phpunit.xml
vendor/bin/phpcs -p
vendor/bin/phpstan analyse
```

If you add features or fix bugs, add/adjust tests in `tests/`.

## Coding standards
- Follow WordPress Coding Standards and project PHPCS rules.
- Sanitize/escape inputs and outputs consistently.
- Prefer `rg` for searching the codebase.
- Keep public APIs backwards compatible when possible.

## Changelog
- Update `CHANGELOG.md` under **Unreleased** with concise, user‑facing notes.

## Submitting changes
- Open a PR with a clear title and description.
- Include steps to test/verify.
- Mention any breaking changes explicitly.
