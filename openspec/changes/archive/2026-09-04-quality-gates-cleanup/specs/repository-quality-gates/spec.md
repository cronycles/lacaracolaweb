## ADDED Requirements

### Requirement: Repository quality checks are clean

The repository SHALL complete PHP formatting, TypeScript linting, frontend build, and Laravel tests without violations attributable to tracked application code.

#### Scenario: Full quality suite succeeds
- **WHEN** a developer runs `php vendor/bin/pint --test`, `npm run lint`, `npm run build`, and `php artisan test` on the cleanup change
- **THEN** each command exits successfully

### Requirement: Quality fixes preserve application behavior

The cleanup SHALL retain existing product behavior while resolving reported formatting and static-analysis violations.

#### Scenario: Targeted checks pass after each cleanup batch
- **WHEN** a formatting or linting batch changes tracked source files
- **THEN** the applicable focused validation passes before the next batch begins