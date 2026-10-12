# 006 — Ordered SDD/TDD tasks

Status: Application implementation explicitly approved on 2026-10-10, including the decisions in requirements.md. Progress and verification are recorded below and in [implementation evidence](evidence/application.md).

For each task: identify acceptance-linked cases, write tests, run and record the meaningful failure before implementation, implement the minimum change, record green results and refactor. Record actual commands, test names/counts, environment and limitations in an implementation evidence document created during approved work. Never mark planned tests as passing.

| Task | Work in order | Traceability |
| --- | --- | --- |
| T-01 | Record approval; reread AGENTS/005/006 and recheck actual dependencies. Before any connection or mutation, write/run negative fail-closed isolation tests for all MySQL, session and Redis connections, URL overrides, cached config, hosts, credentials and run/worker namespaces. Extend the guarded harness for users/sessions/006 schema without destructive resets. Run and record an isolated pre-change baseline only after safeguards pass. Stop on missing 005 services or unsafe configuration. | R-13, R-14 / AC-13, AC-14 |
| T-02 | Write tests for default-denied admin capability and restricted create/grant/revoke/password-reset CLI, hidden credential input, confirmation/cancellation, hashed passwords, redacted audits and session invalidation. Implement minimal migration/provisioning through the existing operator boundary. No account creation against development/production for testing. | R-01, R-02 / AC-01, AC-02 |
| T-03 | Write authentication/session/CSRF/guest/non-admin/revocation tests and real Redis atomic login/admin quota tests. Implement login/logout, admin expiry, recent-password confirmation, gate/policies, scoped safe errors/no-store/body cap and explicit proxy compatibility. Prove middleware ordering and actual Laravel 13 CSRF checks (not the testing bypass). | R-01, R-02, R-11, R-12 / AC-01, AC-02, AC-11, AC-12 |
| T-04 | Test and implement aggregate metric reads, explicit DTOs, question list/details, Unicode literal search, enum filters, bounded cursors and purge races. Verify private fields are absent from every response/error/bootstrap surface and no reads mutate age/status. | R-03, R-04, R-11 / AC-03, AC-04, AC-11 |
| T-05 | Test and implement FAQ private list/details/create/update/activation/deactivation using 005 service and revisions. Cover inactive defaults, no-op edits, content deactivation, duplicates, Unicode/collisions, stale concurrent web/Artisan writes and strict input allowlists. | R-07, R-08 / AC-07, AC-08 |
| T-06 | Test and add revision-checked FAQ delete to shared service and authorized web operation; test recent password/confirmation and FK/original-status/replay consequences. Test and implement independent draft preparation from selected questions, source disappearance and privacy warning contract. | R-05, R-07, R-08, R-10 / AC-05, AC-07, AC-08, AC-10 |
| T-07 | Test and implement authorized single-question deletion with recent password, confirmation, durable restoration metadata before delete and fail-closed sink failure. Cover concurrent retention, no text/identifier leaks, no age/status edits and unchanged 005 retention. Document protected sink and restore requirements without claiming production configuration. | R-06, R-11 / AC-06, AC-11 |
| T-08 | Write failing Vitest tests, then build Blade admin shells and Vue navigation, cards, tables, filters/cursors, detail/draft forms, state badges and API adapter in existing Vite application. Include literal rendering, field errors, loading/empty/success/error, expiry, no automatic write retries, stale-version reload and deletion confirmations. Preserve unsaved text only in memory. | R-09, R-11 / AC-09, AC-11 |
| T-09 | Write/run public chatbot and Artisan regression tests before any necessary integration changes. Verify welcome/admin mount separation, public response/replay, avatar/name/keyboard/IME/focus/unmount behavior and existing Minimal SaaS appearance; complete browser/manual admin accessibility and responsive review when available. | R-09, R-10, R-14 / AC-09, AC-10, AC-14 |
| T-10 | Run full applicable Sail checks after isolation guards, review routes/dependencies/diff/privacy and record acceptance-to-test evidence. Separate automatic and browser findings from deployment/security/privacy gates. Document provisioning, restore/deletion/audit and release runbooks, remaining risks and product approvals; present implementation for review without committing. | All R / AC-01–AC-14 |

## Verification commands after approval and isolation

For database/Redis suites use the existing dedicated runner, not ordinary development services:

```sh
SAIL_FILES=compose.chatbot-tests.yaml APP_SERVICE=chatbot-test-runner ./vendor/bin/sail artisan test --filter=IsolationGuardTest
SAIL_FILES=compose.chatbot-tests.yaml APP_SERVICE=chatbot-test-runner ./vendor/bin/sail artisan test
./vendor/bin/sail npm run test:frontend
./vendor/bin/sail npm run typecheck
./vendor/bin/sail npm run build
./vendor/bin/sail php vendor/bin/pint --test
```

Inspect current runner/bootstrap compatibility before invoking these commands; the guard suite itself must not connect to databases/Redis. No dependency install, migration/reset of development data, Redis flush, cache clear, published-port change or Docker volume deletion is authorized by these tasks. Request approval before changing approved requirements, introducing dependencies or unsafe operations.

## Specification-only evidence (historical)

Specification drafting only: read AGENTS, existing specs and 005 evidence, package manifests, routing/bootstrap, auth/session configuration, models/services, users migration, frontend mounting and isolated test helper. PHP/Composer prerequisite availability was checked. No application tests, database/Redis operations, browser checks or production checks were run for 006. No application implementation or automatic commit was performed. At specification drafting, every implementation task was pending; historical 005 results are not 006 evidence. The implementation update below supersedes this historical status.


## Implementation update — 2026-10-10

Application approval received; tasks were verified/completed in order against the pre-existing partial backend, followed by final regression hardening. See [actual results and AC mapping](evidence/application.md).

| Task | Current status |
| --- | --- |
| T-01 | Complete for this implementation session: connection-free fail-closed guard red/green, guarded harness inspection and current-state baseline recorded before application changes. Earlier partial backend was already present. |
| T-02 | Existing provisioning implemented and verified; historical red-before-green evidence unavailable. |
| T-03 | Authentication/authorization/CSRF/session/real Redis/body/proxy behavior implemented and verified; earlier backend red chronology unavailable. |
| T-04 | Query/metrics/DTO behavior verified; new invalid-cursor fix has red/green evidence. |
| T-05 | FAQ CRUD/concurrency implemented and verified; earlier backend red chronology unavailable. |
| T-06 | FAQ deletion/draft preparation/replay history implemented and verified; earlier backend red chronology unavailable. |
| T-07 | Selected-row deletion and restoration ordering verified; added actual file permission/expiry tests and public-directory safety fix with red/green evidence. Production sink/restore gate pending. |
| T-08 | Complete application implementation and automated checks with red/green evidence for missing UI/page integration and interaction fixes. |
| T-09 | Automated regression/mount checks complete; real-browser layout/zoom/keyboard/spoken accessibility pending. |
| T-10 | Automated checks, scope review, evidence matrix and runbook complete. Manual and production/privacy gates pending; no overall acceptance or deployment claimed. |

Final: PHPUnit 70 tests/864 assertions; Vitest 63 tests; typecheck/build/Pint pass. No current automated failures. Build optional-font optimization warning remains non-failing. AC-13 retains unavailable red chronology for pre-existing partial code. No production data changes, development/production migration/provisioning, destructive reset or automatic commit.
