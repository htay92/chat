# 006 — Application implementation evidence

Date: 2026-10-10. User explicitly approved all four specifications and requested application implementation. Production data was not accessed or changed; no automatic commit.

## Starting state and dependency gate

Read AGENTS.md and all four approved documents. PHP 8.4.25 and Composer 2.10.2 prerequisite checks succeeded; application commands use Sail. Feature 005 models, migrations, normalization, FAQ write/match service, question submission/replay, retention, console workflows and dedicated test infrastructure are present. No missing 005 backend dependency was found. The earlier proposed FAQ deletion extension already exists in partial 006 work.

The working tree already contained partial 006 backend implementation and tests, a minimal login/shell and failing frontend imports. Preserve these changes. No pre-existing red-before-green chronology is available for that backend work; passing reruns do not establish it. New changes below record actual red/green evidence. The baseline is before this turn's application changes, not before that earlier work.

## Ordered verification record

Commands use `SAIL_FILES=compose.chatbot-tests.yaml APP_SERVICE=chatbot-test-runner ./vendor/bin/sail artisan test` for backend checks. The dedicated services have internal-only networking, tmpfs storage, no published ports or development/production data mounts. Connection-free guards run before integration operations.

| Task | Actual result |
| --- | --- |
| T-01 | Initial four connection-free guard tests passed (33 assertions). Review found negative admin assertions caught PHPUnit's RuntimeException-derived assertion failures. Corrected assertions and added unsafe Redis persistent/cluster/replication cases: red `Unsafe Redis routing option accepted` (1 failed/1 passed). Added fail-closed routing checks; green four tests/36 assertions. Full isolated current-state backend baseline: 65 tests/746 assertions passed, 73.261 seconds. Frontend baseline: 49 existing tests passed; admin suite failed import because AdminApp.vue is missing. |
| T-02 | Existing ProvisioningTest rerun: 3 tests/32 assertions passed. Historical red evidence unavailable; no replayed/artificial failure is claimed. |
| T-03 | Existing AuthTest/AdminBoundaryTest rerun: 7 tests/98 assertions passed, including actual CSRF middleware with testing bypass disabled. Redis concurrency/proxy verification follows. |
| T-03 additional | Real isolated AdminLimiterTest: 2 tests/196 assertions passed, including concurrent thresholds, TTL and explicit trusted/untrusted proxy behavior. Expanded AuthTest route matrix and valid-CSRF search/create: 6 tests/99 assertions passed. AdminSecurityTest: 2 tests/19 assertions passed for payload-free audit/failure logging and login/logout session ID/token rotation. |
| T-04 | Added invalid cursor `"0"` coverage: red 200 rather than required 422 (1 failed/2 passed). Replaced the truthiness check with explicit null/empty handling; green QuestionQueryTest 3 tests/29 assertions. |
| T-05 | Existing FaqCrudTest/FaqConcurrencyTest: 4 tests/40 assertions passed. Separate forked MySQL connections produce one successful update and one conflict; stale web activation/deactivation/delete after a service/console edit reject without overwriting. Earlier red history unavailable. |
| T-06 | Existing FaqLifecycleTest: 2 tests/17 assertions passed, covering deletion/FK/replay/original history and independent draft/source disappearance. Earlier red history unavailable. |
| T-07 | Existing QuestionDeletionTest: 1 test/12 assertions passed, including sink-before-delete and fail-closed sink failure. Final hardening added RestorationSinkTest: red public directory expiry did not reject (1 failed/1 passed); fixed pre-delete directory checks; green 2 tests/9 assertions. Owned temporary files verify 0700/0600 permissions, metadata fields and 30-day expiry. Earlier backend red history unavailable. |
| T-08 | Red frontend suite could not import absent AdminApp.vue/api. AdminPagesTest red: shell lacked data-chatbot-admin. Added Vue/CSS/API/mount and private Blade/page routes; green page test 1 test/68 assertions. First UI run exposed missing initial loading status. Expanded interaction tests exposed non-inert modal background; both fixed. The expanded run also had a test selector incorrectly matching Sign out rather than Save; corrected that selector, without changing production behavior. Green 11 admin tests, then final hardening red demonstrated failed edit load sent an unintended creation request; guarded editor save and green 12 admin tests. |
| T-09 | Added admin/public mount boundary and unmount abort tests; existing public widget tests remain unchanged. Final Vitest: 63 tests across 10 files passed (49 existing public tests + 12 admin interaction tests + 2 mount tests). No actual browser/layout/screen-reader evidence is claimed. |
| T-10 | Final guarded PHPUnit 70 tests/864 assertions passed (74.647 seconds). Final Vitest 63 tests passed. Sail typecheck, build and Pint --test passed. Initial Pint check failed on partial 006 formatting; formatting applied only to 006 files/shared integration points and final check passed. git diff --check passed. No package/dependency manifests, public chatbot component/style files or Feature 005 specification IDs changed. |

No migrate:fresh, DROP, Redis flush, shared cache clear, production/development data writes, published-port changes or Docker volume deletion was performed. Sandbox Docker access required normal command approval; all test commands use the dedicated guarded environment.

## Final commands and acceptance mapping

The connection-free guard rerun passed four tests/36 assertions immediately before the final integration suite. All operations used the dedicated runner and owned schemas/namespaces. No skips/failures are reported in final PHPUnit/Vitest totals. The build has a non-failing existing optional fontaine font-optimization warning; no dependency was added to suppress it.

```sh
SAIL_FILES=compose.chatbot-tests.yaml APP_SERVICE=chatbot-test-runner ./vendor/bin/sail artisan test --filter='IsolationGuardTest|AdminIsolationGuardTest'
SAIL_FILES=compose.chatbot-tests.yaml APP_SERVICE=chatbot-test-runner ./vendor/bin/sail artisan test
./vendor/bin/sail npm run test:frontend
./vendor/bin/sail npm run typecheck
./vendor/bin/sail npm run build
./vendor/bin/sail php vendor/bin/pint --test
git diff --check
```

| AC | Actual application evidence / limitations |
| --- | --- |
| AC-01 | ProvisioningTest, AuthTest, AdminSecurityTest: credentials/capability/provisioning/reset, session rotation/logout/expiry and generic login. Real console IAM and deployed session invalidation remain pending. |
| AC-02 | AuthTest checks guest/private JSON, non-admin page/data/mutation access and revocation; actual PreventRequestForgery testing bypass is disabled for negative CSRF matrix and positive valid-token search/create. AdminPagesTest checks authorized page shells. |
| AC-03 | QuestionQueryTest verifies empty/mixed aggregate metrics and unchanged original classification; 005 lifecycle tests preserve historical status. |
| AC-04 | QuestionQueryTest: Unicode search, literal wildcards/backslash, accent sensitivity, strict size/filter/cursor validation, default pages/purge traversal and DTO field allowlists. |
| AC-05 | FaqLifecycleTest and admin.test.ts: independent prefill/blank answer/privacy warning, immutable source, disappearance and deliberate standalone conversion. |
| AC-06 | QuestionDeletionTest and RestorationSinkTest: password expiry/confirmation, authorized single-row deletion, sink ordering/failure, protected metadata files and cutoff. Existing 005 retention tests pass in the full suite. Production independent sink/restore remains pending. |
| AC-07 | FaqCrudTest/FaqLifecycleTest/admin.test.ts: CRUD, inactive default, no-op state, content deactivation, activation preview/confirmation, revision/password deletion and FK/history. Existing 005 empty/inactive/newly activated matching/replay cases pass. |
| AC-08 | FaqCrudTest/FaqConcurrencyTest plus unchanged 005 normalization/model tests: shared Unicode/bounds/duplicates and stale mutation rejection on separate real connections. |
| AC-09 | 12 admin interaction tests verify loading/empty/success/error/expiry/validation/conflict, duplicate-submit prevention, plain text, modal focus trap/restoration/inert background and mobile-menu Escape/focus. Actual viewport/zoom/visual and spoken accessibility evidence is pending. |
| AC-10 | All 49 unchanged public frontend tests, 005 backend tests, FaqConcurrencyTest/FaqLifecycleTest, AdminPagesTest and two mount tests pass. Actual visual/public browser lifecycle evidence and prior 005 manual gaps remain pending. |
| AC-11 | AdminBoundaryTest exact/false-length streamed body cap; QuestionQueryTest/FAQ tests explicit fields and input bounds; AdminSecurityTest content-free audit/exception reporting; Blade/Vue literal rendering and no-store assertions. No private text is bootstrapped in page shells. Proxy/APM redaction remains a production gate. |
| AC-12 | AdminLimiterTest real parallel thresholds/TTL/proxy domains and AuthTest Redis outage. Existing 005 real Redis window/reset tests pass for the shared atomic limiter. Actual deployed TLS/cookies/ingress/proxy chain remains pending. |
| AC-13 | Guard negative tests, baseline and final isolated suite/check evidence recorded. New changes have red-before-green records; earlier partial backend work has no available chronological red record. AC-13 is therefore partial, not fully accepted. |
| AC-14 | Static route/source/dependency/diff review: admin web group protected, no public FAQ/role management/registration/AI or new packages, one Vite frontend, original public files preserved; no automatic commit or production operation. |

## Changed files

- Backend: app/Chatbot/Admin/{AdminConsole,AdminFailure,AdminInput,AdminLimiter,FileRestorationSink,RestorationSink}.php; shared app/Chatbot/FaqService.php; app/Http/Controllers/Admin/{AuthController,FaqController,QuestionController}.php; AdminBoundary/AdminSession middleware; shared ChatbotTrustProxies; Faq/ChatbotQuestion policies; User capability cast and AppServiceProvider gate/binding.
- Routing/configuration: routes/admin.php, routes/web.php, routes/console.php, bootstrap/app.php, config/chatbot_admin.php; incremental 2026_10_09_000006_add_chatbot_admin_to_users migration.
- UI: resources/js/admin/{AdminApp.vue,api.ts,mount.ts,admin.css}, resources/js/app.js, resources/views/admin/{login,shell}.blade.php. Public chatbot components, styles and composable unchanged.
- Verification: tests/Feature/Admin suites (including added AdminPagesTest/AdminSecurityTest), tests/Support/{IsolationGuard,ChatbotTestCase,AdminTestCase}, AdminBoundaryTest/AdminIsolationGuardTest/RestorationSinkTest, tests/frontend/admin.test.ts and admin-mount.test.ts.
- SDD: four 006 status/progress records, this evidence document and operations.md. Existing requirements/acceptance IDs and approved behavior are preserved.

These include the preserved pre-existing partial backend files; they are not all newly created during this turn.

## Outstanding gates and risks

Application code and automated verification are complete for review. No development/production incremental migration or admin provisioning was run. See [operations](../operations.md) for reviewed setup and release steps. Browser screenshots/responsive layout, keyboard with actual browser and spoken accessibility remain pending. Production TLS/proxies/limits, database sessions, IAM, logging/APM, durable independent restoration storage, backup expiry/restore drills, scheduler/backlog monitoring and privacy/security-owner approval remain unverified. Existing 005 historical/manual/external gaps remain unchanged.

Single-role administrators can delete retained questions after password confirmation; security approval of this role and authentication/recovery scope is still a production gate. Production selected-row deletion fails closed until private restoration storage is configured. Filesystem fsync/permissions tests cannot prove independent backup durability. Expiry is bounded to 500 files per scheduled call: operators must monitor and process backlog. A network failure can follow a committed write; the UI warns and never auto-retries. Earlier partial-backend red evidence cannot be reconstructed; final passing checks do not remove that SDD evidence limitation.

## Local setup repair — 2026-10-11

User reported the generic admin-console failure. Read-only Sail checks found operator access allowed, users/sessions tables present and audit directory writable, but `users.is_chatbot_admin` absent. Verified APP_ENV=local, MySQL connection to the Sail `mysql` service and no database URL override. Applied only `2026_10_09_000006_add_chatbot_admin_to_users.php` with Sail artisan migrate --path; migration completed and Schema::hasColumn subsequently returned true. No account was created, credentials requested, question data altered, production data changed or destructive reset performed. Earlier statements about no development migration describe the initial implementation session; this later local repair supersedes that setup status. The user's interactive command still needs retrying; success is not inferred merely from the schema check.
