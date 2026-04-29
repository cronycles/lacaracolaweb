# Role

You are an expert frontend architect with extensive experience in Blade + TypeScript projects applying best practices.

# Task Input

$ARGUMENTS

# Goal

Obtain a step-by-step plan for a frontend task that is ready to start implementing.

# Agent-first execution

- Execute autonomously and end-to-end for the planning scope.
- Ask clarifying questions only when a hard blocker prevents producing a valid plan.
- Do not stop at generic recommendations; produce an implementation-ready plan.

# Process and rules

1. Adopt the role of `ai-specs/.agents/frontend-developer.md`.
2. Analyze the task request provided in the input. If it references a local file, read it directly.
3. Propose a step-by-step plan for the frontend scope, covering everything in the task and applying project rules from `docs/tech-doc.mdc`, focusing mainly on the frontend doc when present.
4. Apply the best practices of your role to ensure the developer can be fully autonomous and implement the task end-to-end using only your plan.
5. Do not write code yet; provide only the plan in the output format defined below.
6. If you are asked to start implementing at some point, make sure the first thing you do is to move to a proper feature branch and follow the process described in `/develop-frontend.md`.

# Output format

Markdown document at the path `changes/[task_slug]_frontend.md` containing the complete implementation details.
If OpenSpec is not installed or the `changes/` folder does not exist, use fallback path `docs/plans/[task_slug]_frontend.md`.
Follow this template:

## Frontend Implementation Plan Task Template Structure

### 1. **Header**

- Title: `# Frontend Implementation Plan: [TASK] [Feature Name]`

### 2. **Overview**

- Brief description of the feature and frontend architecture principles (Blade component architecture, progressive enhancement, TypeScript modules)

### 3. **Architecture Context**

- Components/services involved
- Files referenced
- Route/view considerations (if applicable)
- Client-side state handling approach (if applicable)

### 4. **Implementation Steps**

Detailed steps, typically:

#### **Step 0: Create Feature Branch**

- **Action**: Create and switch to a new feature branch following the development workflow. Check if it exists and if not, create it
- **Branch Naming**: Follow the task branch naming convention (`feature/<task-slug>`).
- **Implementation Steps**:
    1. Ensure you're on the latest `main` or `develop` branch (or appropriate base branch)
    2. Pull latest changes: `git pull origin [base-branch]`
    3. Create new branch: `git checkout -b [branch-name]`
    4. Verify branch creation: `git branch`
- **Notes**: This must be the FIRST step before any code changes. Refer to `docs/tech-doc.mdc`, focusing mainly on the **frontend** doc when present.

#### **Step N: [Action Name]**

- **File**: Target file path
- **Action**: What to implement
- **Function/Component Signature**: Code signature
- **Implementation Steps**: Numbered list
- **Dependencies**: Required imports
- **Implementation Notes**: Technical details

Common steps:

- **Step 1**: Update/Create TS modules in `resources/ts/components/` and shared utilities in `resources/ts/utils/` (if needed)
- **Step 2**: Create/Update Blade components in `resources/views/components/`
- **Step 3**: Update Blade pages in `resources/views/`
- **Step 4**: Update locale-aware routes in `routes/web.php` (if needed)
- **Step 5**: Add/Update tests according to existing project approach

#### **Step N+1: Update Technical Documentation**

- **Action**: Review and update technical documentation according to changes made
- **Implementation Steps**:
    1. **Review Changes**: Analyze all code changes made during implementation
    2. **Identify Documentation Files**: Determine which documentation files need updates based on:
        - AJAX endpoint/payload changes → Update `docs/specific-tech-frontend-doc.mdc` and `docs/specific-tech-backend-doc.mdc` when applicable
        - UI/UX patterns or component patterns → Update `docs/specific-tech-frontend-doc.mdc`
        - Routing changes → Update routing documentation
        - New dependencies or configuration changes → Update `docs/specific-tech-frontend-doc.mdc`
        - Test patterns or Cypress changes → Update testing documentation
    3. **Update Documentation**: For each affected file:
        - Update content in English (as per `docs/tech-doc.mdc`)
        - Maintain consistency with existing documentation structure
        - Ensure proper formatting
    4. **Verify Documentation**:
        - Confirm all changes are accurately reflected
        - Check that documentation follows established structure
    5. **Report Updates**: Document which files were updated and what changes were made
- **References**:
    - Follow process described in `docs/tech-doc.mdc`
    - All documentation must be written in English
- **Notes**: This step is MANDATORY before considering the implementation complete. Do not skip documentation updates.

### 5. **Implementation Order**

- Numbered list of steps in sequence (must start with Step 0: Create Feature Branch and end with documentation update step)

### 6. **Testing Checklist**

- Post-implementation verification checklist
- Component functionality verification
- Error handling verification

### 7. **Error Handling Patterns**

- Error state management in components
- User-friendly error messages
- AJAX/form error handling in TS modules and controllers

### 8. **UI/UX Considerations** (if applicable)

- Existing component/style patterns usage
- Responsive design considerations
- Accessibility requirements
- Loading states and feedback

### 9. **Dependencies**

- External libraries and tools required
- Third-party packages (if any)

### 9.1 **Quality Gates**

- `npm run lint`
- `npm run build`

### 10. **Notes**

- Important reminders and constraints
- Business rules
- Language requirements (English only for technical artifacts)
- TypeScript vs Blade integration considerations

### 11. **Next Steps After Implementation**

- Post-implementation tasks (documentation is already covered in Step N+1, but may include integration, deployment, etc.)

### 12. **Implementation Verification**

- Final verification checklist:
    - Code Quality
    - Functionality
    - Testing
    - Integration
    - Documentation updates completed
