## 1. Inventory and boundaries

- [ ] 1.1 Regenerate the repository-wide Pint and ESLint violation inventory and identify only tracked source files requiring changes.
- [ ] 1.2 Classify each finding as mechanical formatting or a behavior-sensitive lint fix, and identify focused validation for each group.

## 2. PHP formatting

- [ ] 2.1 Apply Pint only to the PHP files reported by the current inventory, in reviewable batches.
- [ ] 2.2 Run focused Laravel tests for any formatted files that contain executable behavior or tests.
- [ ] 2.3 Verify `php vendor/bin/pint --test` passes repository-wide.

## 3. TypeScript linting

- [ ] 3.1 Fix the `gallery.ts` browser-global violations without changing gallery behavior.
- [ ] 3.2 Fix the unused `burgerLines` violations in `mobile-nav.ts` without changing navigation behavior.
- [ ] 3.3 Run focused checks for the edited modules and verify `npm run lint` passes repository-wide.

## 4. Final validation

- [ ] 4.1 Run `npm run build` and address only cleanup-caused build failures.
- [ ] 4.2 Run `php artisan test` and address only cleanup-caused failures.
- [ ] 4.3 Record final quality-gate results and any unrelated pre-existing findings in the change artifacts.