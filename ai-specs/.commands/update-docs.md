Use `docs/project-doc.mdc` to update all documentation impacted by the implemented changes.

Agent-first behavior:

- Execute documentation updates end-to-end for all impacted files.
- Ask clarifying questions only when impact cannot be determined from available context.
- Do not stop at listing files; apply the updates and report what changed.

Checklist:

1. Review code changes and identify affected documentation surfaces.
2. Update the relevant files in `docs/` affected by the changes.
3. Review `README.md` and update it if setup, scripts, behavior, or workflow information changed.
4. Keep documentation in English and consistent with existing structure.
5. Ensure examples, paths, and commands reflect the current project state.
