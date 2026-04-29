Please analyze and implement the backend task request: $ARGUMENTS.

Agent-first execution rules:

- Execute the task end-to-end without waiting for extra prompts.
- Ask clarifying questions only if a real blocker prevents safe implementation.
- Do not stop at analysis; implement, verify, and report outcomes.

Documentation update rule (MANDATORY):

- Any significant code, architecture, or feature change must update impacted files in `docs/` in the same change set.
- Update `README.md` whenever setup, scripts, deployment, behavior, or workflow instructions changed.

Follow these steps:

1. Resolve and read the task request details first (goal, scope, constraints, acceptance criteria).
2. Understand the backend problem and map impacted areas in this repository (`app/`, `routes/`, `database/`, `tests/`, and `docs/`).
3. Create or switch to a task branch following this convention:
    - `feature/<task-slug>`
    - Example: `feature/refactor-activity-filters`
4. Implement the task in small incremental steps, following project standards in `docs/tech-doc.mdc`, especially the **backend** standards referenced from that document.
5. Add or update backend tests in `tests/Feature` and `tests/Unit` according to the change scope.
6. Run backend quality gates from project root:
    - `composer run pint`
    - `composer test`
7. If endpoint behavior changed, update `docs/specific-tech-backend-doc.mdc` when applicable. If the data model changed, update `docs/specific-data-model.md` when applicable. Review `README.md` when relevant.
8. Stage only files related to the task, leaving unrelated working tree changes untouched.
9. Create one descriptive commit message in English.
10. Push the branch and create/update PR with `gh` targeting `develop`.
11. Never merge to `main` from this command. `main` merges are manual and done by the project owner when releasing to production.

Remember to use the GitHub CLI (`gh`) for all GitHub-related tasks.
