# GitHub Projects V2 — canonical project setup

## Source of truth

**GitHub Project V2 #5 is the canonical planning, workflow and roadmap entrypoint.** Its items must link to GitHub Issues/PRs; actual implementation is established by code and green checks on `main`. Conversations are proposals only. Do not create competing Markdown roadmap generators, scheduled status synchronizers or separate project plans.

Current state (2026-11-25): Owner screenshots confirm five Status options (Backlog, Ready, In Progress, In Review, Done), Priority (P0/P1/P2), the built-in Milestone column, and All Items / Board / Backlog / Roadmap layouts. A real temporary issue #104 was auto-added by GitHub's Project automation and moved to Done after closure (GitHub REST issue events and owner screenshot). PR auto-add/merge workflows are enabled in UI but **not yet independently proven on a real post-configuration PR**. The connected GitHub tool can read issue event history but cannot directly edit Project V2 views or custom fields.

Project URL: **https://github.com/users/fusumivietnam/projects/5** (GraphQL-access verified; six Issues imported)

Owner: `fusumivietnam`; Project name displayed in owner screenshots: `Student Perks Hub - Laravel`; repository: `fusumivietnam/student-perks-laravel`.

## Initial setup in GitHub UI

1. Open the existing [Project #5](https://github.com/users/fusumivietnam/projects/5); do not create another Project. Verify its title and ownership.
2. In project settings, add the `student-perks-laravel` repository as a linked repository if available, and configure an auto-add workflow for new repository issues/PRs where supported.
3. Add existing milestone Issues **#5 (M0), #1 (M1), #2 (M2), #3 (M3), #4 (M4)**; do not duplicate them as draft items.
4. Use the existing **Status** (Backlog, Ready, In Progress, In Review, Done), the custom **Priority** (P0, P1, P2), and GitHub's **built-in Milestone** field. The five M0–M4 roadmap issues are identified by issue title and canonical issue number; do not create a second custom Milestone field. The owner explicitly chose not to require dates.
5. Views verified by owner screenshots: **All Items** (unfiltered table), **Board** (columns by Status), **Backlog** (table filter `-status:Done`, sorted Priority), and **Roadmap** (timeline layout with Dates: None). Owner may optionally rename/convert Roadmap into a **Milestones** table for M0–M4; a date field is **not** a prerequisite for governance acceptance.
6. Native workflows enabled in owner screenshots: auto-add matching repository issues/PRs (`is:issue,pr`), new items → Backlog, item closed → Done, PR linked to issue → In Progress, PR merged → Done, and auto-add sub-issues. **Auto-close issue and bidirectional Sync with issue status are OFF** to avoid prematurely closing launch Issue #4. Live issue #104 verified the auto-add/status-change and closure/status-change events; check the PR workflows on the next real PR before marking them E2E verified.
7. Project #5 remains the sole roadmap. Close Issue #6 only after reviewing the next legitimate PR's Project auto-add and merge→Done events, or explicitly documenting any unavailable evidence. Do not fabricate dates, create duplicate roadmaps, or open PRs solely for an artificial automation probe.

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

`docs/ROADMAP.md` is a historical pointer/snapshot, **not** an authoritative live roadmap. The custom `roadmap-sync.yml` and `scripts/sync-roadmap.cjs` are retired; no new custom sync is needed. Project #5 is the canonical roadmap. Six Issue memberships were imported by GitHub Actions GraphQL and are visible in Project. Owner screenshots and actual Issue #104 events verify the native Issue workflows; PR lifecycle verification remains open.

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

History: Previous run #41863901123 failed due to missing token. [Read-only validation #41992895400](https://github.com/fusumivietnam/student-perks-laravel/actions/runs/41992895400) and [apply run #41993142767](https://github.com/fusumivietnam/student-perks-laravel/actions/runs/41993142767) passed, importing six issues. Do not rerun the one-off import. Live workflow evidence: [Issue #104](https://github.com/fusumivietnam/student-perks-laravel/issues/104) and [Governance Issue #6](https://github.com/fusumivietnam/student-perks-laravel/issues/6). Source release candidate `a92f966b002b2842bb181f764ad345cf05fafde8` is CI-green, while production Issue #4 remains open.
