# Acorn Safety Healthcheck

A client-first WordPress Health & Safety self-assessment for Acorn Safety Services.

## Requirements

- WordPress 6.5+
- PHP 8.1+
- Composer 2

## Install

Run `composer install --no-dev --optimize-autoloader`, copy the repository to `wp-content/plugins/acorn-safety-healthcheck`, activate it, and add `[acorn_safety_healthcheck]` to the `/health-and-safety-healthcheck/` page.

## Development

- `composer test:unit`
- `composer test:integration`
- `npm run wp:start`
- `npm run test:e2e`

The server is authoritative for question applicability and all findings. Never expose assessment/report token hashes or snapshot internals.
