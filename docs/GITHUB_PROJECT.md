# GitHub Projects V2 — canonical project setup

## Source of truth

**GitHub Project V2 #5 is the canonical planning, workflow and roadmap entrypoint.** Its items must link to GitHub Issues/PRs; actual implementation is established by code and green checks on `main`. Conversations are proposals only. Do not create competing Markdown roadmap generators, scheduled status synchronizers or separate project plans.

Current state: The Project URL was provided by the project owner on 2026-11-03 and is registered below. The connected GitHub API does not expose Projects V2 read/edit operations; its items, views, fields, and automations have **not been independently verified**.

Project URL: **https://github.com/users/fusumivietnam/projects/5** (owner-provided; configuration unverified)

Owner: `fusumivietnam`; Project name: `Student Perks Hub`; repository: `fusumivietnam/student-perks-laravel`.

## Initial setup in GitHub UI

1. Open the existing [Project #5](https://github.com/users/fusumivietnam/projects/5); do not create another Project. Verify its title and ownership.
2. In project settings, add the `student-perks-laravel` repository as a linked repository if available, and configure an auto-add workflow for new repository issues/PRs where supported.
3. Add existing milestone Issues **#5 (M0), #1 (M1), #2 (M2), #3 (M3), #4 (M4)**; do not duplicate them as draft items.
4. Define fields: **Status** (Backlog, Ready, In Progress, In Review, Done), **Priority** (P0, P1, P2), **Milestone** (M0–M4), **Target Date** (date, optional), **Size** (XS/S/M/L, optional). Do not recreate a second status in labels.
5. Create views: **Roadmap** (timeline by Target Date, group by Milestone); **Board** (group by Status); **Backlog** (table, sort Priority); **Milestones** (table filter issue titles [M0]–[M4]).
6. Enable built-in workflows: auto-add matching repository issues/PRs, set new items to Backlog, move linked items to In Progress when work starts, and move to Done on issue closure/PR merge **only if their review gate is actually met**. Built-in automations vary by project configuration; verify on an example issue.
7. The Project URL is recorded above. Once views and automations are verified, close Issue #6 with explicit evidence. Do not create any other roadmap.

## Acceptance and status semantics

- **Project Status** = operational workflow state. An issue moved to Done is not proof of a production deployment.
- **CI** proves test/build execution for an exact commit. **PR** proves integration only after merge.
- **Milestone completion** requires checking its acceptance gate, not relying on date, issue closure or status alone.
- Use dependencies and sub-issues rather than manually maintained percentages.
- If extra native workflow automation isn't supported by account permissions/plan, use the default issue/PR workflows, not custom code.

## Current milestone records

| ID | Canonical Issue | Scope |
|---|---|---|
| M0 | [#5](https://github.com/fusumivietnam/student-perks-laravel/issues/5) | Laravel/Docker/PostgreSQL foundation |
| M1 | [#1](https://github.com/fusumivietnam/student-perks-laravel/issues/1) | Data model + Filament admin |
| M2 | [#2](https://github.com/fusumivietnam/student-perks-laravel/issues/2) | Public offers and discovery |
| M3 | [#3](https://github.com/fusumivietnam/student-perks-laravel/issues/3) | Editorial content + SEO |
| M4 | [#4](https://github.com/fusumivietnam/student-perks-laravel/issues/4) | VPS launch |

## Transition

`docs/ROADMAP.md` is a historical pointer/snapshot, **not** an authoritative live roadmap. The custom `roadmap-sync.yml` and `scripts/sync-roadmap.cjs` are retired; no new custom sync is needed. Project #5 is the registered roadmap entrypoint. Until its fields/items can be verified, GitHub Issues remain canonical individual task records; do not claim full integration.

## API access — initial integration

The user-owned Project #5 requires a credential with GitHub Projects permissions; the repository-scoped default GitHub Actions `GITHUB_TOKEN` does **not** suffice. The connected ChatGPT GitHub connector does not expose generic GraphQL/Projects V2 tools, so adding a secret to Actions will enable **the workflow**, not directly grant ChatGPT a new connector capability.

Recommended initial low-friction setup:

1. Visit https://github.com/settings/tokens and choose **Generate new token (classic)**. For this **user-owned** Project, grant `project`. Add only the repository scopes GitHub requires for your issue access (the official Actions example uses `repo`; avoid broader permissions unless actually needed). Set a short expiration and revoke/rotate after use.
2. **Do not paste tokens into any chat, issue, PR or tracked file.** In https://github.com/fusumivietnam/student-perks-laravel/settings/secrets/actions choose **New repository secret**, name it exactly `PROJECT_V2_TOKEN`, and paste the secret value into GitHub's secure field.
3. Run https://github.com/fusumivietnam/student-perks-laravel/actions/workflows/project-v2-setup.yml on `main` with **apply=false** to validate API access without changing Project items.
4. Inspect the GitHub Actions run. If it reports access to Project #5 and lists missing Issues, run it again with **apply=true**. This is idempotent: it adds only missing existing Issues #5, #1, #2, #3, #4 and #6.
5. In Project #5, configure built-in views, Status/Priority/Milestone fields and auto-add Workflows as defined above. This one-time API workflow does **not** create views or configure Project's built-in automation.
6. Once the project is configured and verified, remove or rotate the temporary classic PAT. If continued API automation is explicitly required, replace the temporary credential with a narrowly scoped, rotatable credential and an approved integration design.

The setup script is at `scripts/setup-project-v2.cjs`. It checks GraphQL access to `user(login:"fusumivietnam"){projectV2(number:5)}`, checks current repository Issues and adds missing Project items using `addProjectV2ItemById`. It intentionally never prints the token, makes no destructive requests and does not modify issue states.

Status: **Workflow and script committed; secret not verified, API execution not performed, project item membership not verified.**
