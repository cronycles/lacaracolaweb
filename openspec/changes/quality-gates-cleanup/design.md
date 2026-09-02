## Context

The completed external iCalendar change passed its focused checks and the Laravel suite, but repository-wide Pint and ESLint remain red due to existing violations outside that change. The current known ESLint findings are undefined `navigator` references in `resources/ts/components/gallery.ts` and unused `burgerLines` variables in `resources/ts/components/mobile-nav.ts`; the exact Pint file set must be regenerated at implementation time.

## Goals / Non-Goals

**Goals:**
- Make repository-wide Pint and ESLint checks pass.
- Preserve existing backend, frontend, test, and deployment behavior.
- Validate every mechanical cleanup batch with the narrowest applicable check, then run the full quality suite.

**Non-Goals:**
- No feature work, database migrations, dependency upgrades, or visual redesign.
- No broad refactors beyond changes required to resolve a reported quality violation.
- No suppression or disabling of lint/formatting rules.

## Decisions

### Treat command output as the source of truth

Regenerate Pint and ESLint output before modifying files, then group fixes by tool and affected subsystem. The prior report is routing context, not a frozen inventory, because the worktree may have changed.

### Use mechanical formatting where semantics are unchanged

Apply Pint only to files it reports. For TypeScript, make the smallest semantic corrections: use a browser-safe global reference where needed and remove or consume unused values according to the module's established behavior. Avoid config exceptions because the checks are intended to catch regressions.

### Validate progressively, then globally

After each group, run Pint or ESLint on that group. Finish with `php vendor/bin/pint --test`, `npm run lint`, `npm run build`, and `php artisan test`.

## Risks / Trade-offs

- Mechanical formatting may touch many files -> Limit edits to files reported by Pint and review each batch before continuing.
- An ESLint fix can change browser behavior -> Add focused regression coverage or build verification for the affected module.
- Existing unrelated test failures can obscure quality work -> Record failures separately and do not expand scope unless caused by the cleanup.