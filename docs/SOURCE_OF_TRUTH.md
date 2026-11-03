# GitHub — project single source of truth

Project: `fusumivietnam/student-perks-laravel`. User conversations are context/proposals, never authoritative status.

## Authority

1. GitHub code, lockfiles, migrations and checked CI runs on `main` are the authority on implementation.
2. **GitHub Projects V2** is the intended primary roadmap, backlog and workflow status view. See [Project setup](GITHUB_PROJECT.md); **the actual Project has not yet been created/linked**.
3. GitHub Issues capture deliverables, milestone acceptance and bugs; Pull Requests record implementation/review. Until the Project is connected, Issues remain the task authority.
4. `PRODUCT.md`, `ARCHITECTURE.md` and `DELIVERY.md` govern approved scope, architecture and acceptance.
5. `ROADMAP.md` is a non-live historical pointer, not an independent roadmap. No custom roadmap sync workflow is required.

## Working protocol

Every development turn: inspect GitHub main, project board if connected, open Issues/PRs and latest relevant Actions; choose the highest priority unblocked issue; implement a meaningful vertical slice; verify tests; link Issue to PR and record evidence on GitHub. Summarize the GitHub outcome in chat without creating competing status.

## Status rules

Project workflow statuses: Backlog → Ready → In Progress → In Review → Done. Prioritize with P0/P1/P2. Never assume a green workflow, closed Issue or merged PR by itself means production is live; acceptance must be verified. Keep work items linked to repository Issues/PRs.

No supplementary roadmap renderer, manual status snapshots or parallel task-tracker. If the Projects integration is unavailable, state that constraint without claiming the board has been created.
