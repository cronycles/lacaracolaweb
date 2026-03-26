---
description: "Use when creating or modifying any file inside the docs/ directory, or when updating README.md. Covers writing language, structure, cross-references and update obligations."
applyTo: "docs/**"
---

# Documentation Conventions — La Caracola Web

## Language Rules
- All files in `docs/`: written in **Italian**.
- `README.md` in project root: written in **English**.
- Code comments referenced inside docs: keep them in English.

## Structure
- `docs/requirements.md` — Product requirements, functional and non-functional. Source of truth for scope.
- `docs/roadmap.md` — Development phases, priorities and dependencies.
- `docs/content-model.md` — Decisions on config vs database, data entity schema.
- `docs/dev-instructions.md` — Developer workflow guide (links to `.github/` instructions).

## Cross-Reference Rules
- When adding a new documentation file, add it to the index in `README.md` and in `.github/copilot-instructions.md`.
- When a feature spans multiple docs (e.g. new entity affects both `requirements.md` and `content-model.md`), update all of them.
- Always link to relevant docs sections from `.github/instructions/` files rather than duplicating content.

## Update Obligation
Any significant code or architecture change must be reflected in the relevant `docs/` file before the task is considered done.

## Writing Style
- Use concise bullet points over long paragraphs.
- Section headings should clearly indicate scope (`##` for major sections, `###` for sub-sections).
- Keep docs up to date — outdated docs are worse than no docs.
