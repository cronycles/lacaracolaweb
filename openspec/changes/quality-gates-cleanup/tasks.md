## 1. Inventory and boundaries

- [x] 1.1 Regenerate the repository-wide Pint and ESLint violation inventory and identify only tracked source files requiring changes.
- [x] 1.2 Classify each finding as mechanical formatting or a behavior-sensitive lint fix, and identify focused validation for each group.

## 2. PHP formatting

- [x] 2.1 Apply Pint only to the PHP files reported by the current inventory, in reviewable batches.
- [x] 2.2 Run focused Laravel tests for any formatted files that contain executable behavior or tests.
- [x] 2.3 Verify `php vendor/bin/pint --test` passes repository-wide.

## 3. TypeScript linting

- [x] 3.1 Fix the `gallery.ts` browser-global violations without changing gallery behavior.
- [x] 3.2 Fix the unused `burgerLines` violations in `mobile-nav.ts` without changing navigation behavior.
- [x] 3.3 Run focused checks for the edited modules and verify `npm run lint` passes repository-wide.

## 4. Final validation

- [x] 4.1 Run `npm run build` and address only cleanup-caused build failures.
- [x] 4.2 Run `php artisan test` and address only cleanup-caused failures.
- [x] 4.3 Record final quality-gate results and any unrelated pre-existing findings in the change artifacts.

## Results

- `php vendor/bin/pint --test`: pass (112 files fixed; all mechanical formatting — spacing, import order, phpdoc alignment, quotes, blank lines, class-definition brace style; no behavior changes).
- `npm run lint`: pass (added `navigator` to the browser globals list in `eslint.config.js`; removed the unused `burgerLines` lookup in `mobile-nav.ts`, whose per-span animation is already driven by CSS via the `aria-expanded` attribute).
- `npm run build`: pass.
- `php artisan test` (via `phpunit` directly, sqlite extensions enabled): 169 tests, 679 assertions, all passing.
- Notable fix beyond mechanical formatting: Pint's `php_unit_method_casing` fixer renamed the anonymous fake driver's `testDraft` method to `test_draft` inside `tests/Feature/GuestReportingValidationTest.php`, breaking the `GuestReportingDriverInterface` contract (fatal error). Extracted the fake driver into a named class `Tests\Fixtures\FakeGuestReportingDriver` (not extending `TestCase`), which resolves the misfire while preserving identical behavior.
- No unrelated pre-existing quality-gate failures were found.
