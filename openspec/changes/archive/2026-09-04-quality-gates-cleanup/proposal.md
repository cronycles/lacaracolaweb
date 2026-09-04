## Why

Repository-wide Pint and ESLint checks currently fail despite a passing application test suite. Restoring these quality gates now prevents unrelated formatting and static-analysis debt from obscuring future regressions.

## What Changes

- Resolve all current Pint violations across tracked PHP files without changing application behavior.
- Fix current ESLint violations in frontend TypeScript modules without changing user-visible behavior.
- Add or adjust focused regression coverage when a lint fix changes executable code.
- Establish `php vendor/bin/pint --test`, `npm run lint`, `npm run build`, and `php artisan test` as passing completion checks.

## Capabilities

### New Capabilities
- `repository-quality-gates`: Repository formatting, static analysis, frontend build, and Laravel test checks remain executable without violations.

### Modified Capabilities

None.

## Impact

- PHP, Blade-adjacent backend code, tests, seeders, migrations, and utility scripts affected by Pint.
- TypeScript modules reported by ESLint.
- CI/local developer quality-check workflow; no public routes, database schema, or product behavior changes are intended.