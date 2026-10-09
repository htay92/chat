# Feature 005 application evidence

Application implementation approved explicitly by the user in this session. Production infrastructure and privacy-policy release are not approved/verified by this authorization.

## T-01

- Prerequisite checks: PHP 8.4.25 and Composer 2.10.2 available; application commands use Sail PHP 8.5.
- Sail php -m: intl, mbstring, pdo_mysql, redis, pcntl present.
- Red: Sail artisan test --filter=IsolationGuardTest: 2 errors (missing IsolationGuard), 0 assertions; no database/Redis operations.
- Green: same command: 2 tests, 11 assertions, passed. Negative cases reject local environment, development hosts, generic schema, root/empty credentials, missing namespace, URL overrides and cached configuration.
- Dedicated test compose uses separate internal network, tmpfs MySQL/Redis storage, no host ports or development volumes. Runner uses existing Sail image; no new dependency.

## Ordered red/green record

All PHP/Artisan/npm checks were run through Laravel Sail. Real-service tests used SAIL_FILES=compose.chatbot-tests.yaml and APP_SERVICE=chatbot-test-runner; no development/production database or Redis operations were performed.

| Task | Red evidence | Green evidence / implemented result |
| --- | --- | --- |
| T-02 | IsolationGuardTest/NormalizerTest/FaqTest: 7 tests, 5 missing-class/command errors, 2 passed. | Same filter: 7 tests, 50 assertions passed. An intermediate failure was a test-input mistake: Laravel expectsConfirmation takes yes/no strings, not booleans; corrected without changing production behavior. FAQ normalization, inactive defaults, validated writes, duplicate rejection, stale revision checks, command confirmation and opt-in non-overwriting seeder implemented. |
| T-03 | QuestionStorageTest: 3 missing-class errors. | 3 tests, 12 assertions passed. HMAC-only question schema, immutable classification, nullable FAQ FK and read-only bounded review implemented. |
| T-04 | AnswerServiceTest/SubmissionConcurrencyTest: 4 missing-service errors and 1 failed concurrency assertion. | 5 tests, 22 assertions passed. Transactional collection, exact matching, replay/conflict semantics and real separate-process MySQL tuple deduplication implemented. |
| T-05 | ApiTest: 5 failures from absent route/404. | 5 tests, 48 assertions passed. Intermediate strict-type failure exposed validation continuing after a string-rule failure; bail fixed it. Installed Laravel Pipeline handles inner exceptions itself, so dedicated exception rendering/report suppression is registered as well as early boundary handling. No payload/SQL details are returned or logged. |
| T-06 | RateLimitTest: 2 missing-limiter errors and 3 failed quota/unavailable-Redis assertions. | Real Redis threshold/non-sliding TTL, 16-process atomic acquisition, IP-first attempt charging, fail-closed Redis outage, spoofed/trusted proxy checks and real 60-second reset passed. Intermediate Redis fork test inherited the parent's MySQL socket; harness now rolls back/disconnects before fork and resumes an independent transaction afterward. Targeted rerun: 5 tests, 72 assertions passed. |
| T-07 | RetentionTest: 2 missing-command errors and 1 absent-schedule failure. | 3 tests, 15 assertions passed. Exact cutoff including newer-by-one-second boundary, 501-row batching, idempotent purge, exact protected-session deletion and daily UTC/non-overlap scheduling implemented. |
| T-08 | apiResponder.test.ts: failed import because adapter did not exist; no tests executed. | 10 tests passed. Cryptographic tab identity, storage denial/reload/24-hour rotation, request contract, timeout/abort and protocol errors implemented. |
| T-09 | apiWidget.test.ts: 4 failed tests (missing context, Retry wait state, new notice and Unicode whitespace handling). | Full frontend suite: 45 tests in 8 files passed. Production responder now uses API; injected local mock tests retained. Retry preserves exchange IDs and user entries, rate-limit wait disables Retry, current answer is rendered literally, and existing focus/keyboard/avatar/name regressions pass. |
| T-10 | Initial Pint --test failed on formatting. Added guard cases failed on unsafe driver/socket/port/credential overrides; malformed proxy mask returned 200 instead of required 503; 3 protocol tests accepted array enums/Unicode blank answers. Protected stdin unit test initially failed on missing FaqInput. | Scoped Pint fixes, stricter pre-connection checks, CIDR-mask validation and exact enum/Unicode answer validation passed their targeted reruns. FaqInput test: 1 test/4 assertions passed; FAQ workflow suite including independent multiline confirmation: 8 tests/63 assertions passed. Final full results are below. |

Additional acceptance regressions cover multilingual exact matching and injected hash collisions, command edits/deactivation/no-op/denied access, attributable content-free audit metadata, privacy-safe database failure responses/logs, 409 without text echo, review cursors across deletion, streamed bounded-body reads, and shared PHP/Vue Unicode fixtures. These regression tests exercise application behavior; they do not prove production infrastructure controls.

## Final verification

- Vitest: 49 tests across 8 files, passed (Sail npm run test:frontend).
- TypeScript: Sail npm run typecheck, passed.
- Production Vite build: Sail npm run build, passed. Existing font plugin emits an optional fontaine optimization warning; no dependency was installed and the build succeeds.
- Pint: Sail php vendor/bin/pint --test, passed after scoped formatting fixes.
- git diff --check: passed. R-01–R-14, AC-01–AC-14 and T-01–T-10 IDs preserved.
- Final full isolated PHPUnit run: 41 tests, 304 assertions, passed; 64.678 seconds including the real Redis reset window. No tests skipped in this isolated run.

## Acceptance evidence matrix

| Criteria | Automated application evidence | Remaining evidence |
| --- | --- | --- |
| AC-01 | ApiTest, AnswerServiceTest: committed new/replayed responses, empty table, inactive/activated/new FAQ, fallback, current prepared answers; source/package review shows no AI or outbound backend provider. | Actual deployed browser requests pending. |
| AC-02 | ApiTest, BodyBoundaryTest, NormalizerTest, validation.test.ts and shared fixture JSON: schema/types, code-point bounds, Unicode edges/newlines, exact byte cap, false/missing declared length and resource-stream read bounds before downstream parsing. | Production ingress/PHP request handling/rejection formats pending. |
| AC-03 | QuestionStorageTest; apiResponder.test.ts/apiWidget.test.ts: HMAC-only storage, secure UUIDs, page-memory fallback, tab reload/24-hour rotation, immutable retry IDs and no transcript storage. | Actual deployed browser Storage/Network check and tab-cloning observation pending. |
| AC-04 | FaqTest, FaqInputTest, NormalizerTest: validated FAQ authoring/revisions, inactive transitions, cancellations, exact multilingual matching, collision guard, unique keys, demo seeder, metadata-only audit, multiline stdin contract and independent confirmation seam. | Actual protected stdin/controlling-terminal interaction, individual OS/SSH/IAM authorization, attributable audit access/permissions pending. |
| AC-05 | AnswerServiceTest, SubmissionConcurrencyTest, ApiTest, RateLimitTest, apiWidget.test.ts: one committed row in parallel MySQL race, replay classifications/replies, fresh logical submissions, generic conflict, Retry ID reuse and replay quota consumption. | Deployment networking/request retries pending. |
| AC-06 | QuestionStorageTest, FaqTest, ApiTest; migration/source review: preserved answered status after FK deletion, indexed storage, bounded escaped original-miss review, cursors after deletion, no public management/history routes or identity exposure. | Production authorization and audit review pending. |
| AC-07 | RateLimitTest: isolated real Redis atomic quota/16-process checks, first-attempt fixed window, non-sliding TTL, real 60-second reset, malformed/replay/session-denial charging, unavailable Redis and trusted/untrusted proxy behavior; explicit dedicated connection remains independent of default array cache. | Production proxy topology/header sanitization/direct-access restrictions and Redis access controls pending. |
| AC-08 | ApiTest/FaqTest/BodyBoundaryTest and widget.test.ts/apiWidget.test.ts: independent Origin, cookie-free requests, no-store, plain text/SQL-safe persistence, generic errors, sanitized failure logs/audit and literal HTML-like display. | Actual deployment HTTPS/Origin/logging/APM/proxy/database access and privacy-owner checks pending. |
| AC-09 | widget.test.ts/apiWidget.test.ts and diff review: exact notice/error copy, Demo identity, preserved decorative avatars/speaker labels/keyboard/focus/log semantics. widget.css and ChatTranscript are unchanged. | Measured geometry/contrast/zoom/mobile keyboard/safe areas/reduced motion and actual screen-reader speech pending; prior 001/004 gaps stay pending. |
| AC-10 | RetentionTest and AnswerServiceTest: UTC cutoff, >500 rows, statuses/newer-row preservation, replay age, repeated purge, exact early deletion/dedup removal, schedule/non-overlap; command failures are generic and contain no submitted tokens. | Actual scheduled execution/missed-run alerts and restore-time deletion instructions pending. |
| AC-11 | No production/privacy compliance is inferred from tests; runbook documents policy/backup/scheduler gates. | Entire production scheduling/alerts, backup expiry/access, restore purge/deletion drill, and full privacy/product policy sign-off pending. |
| AC-12 | apiResponder.test.ts/apiWidget.test.ts/useChatbot.test.ts/widget.test.ts/mockResponder.test.ts: exact API contract, fallback success, current answer independent of status, safe protocol errors, 429 wait, request-context retries, timeout/abort, no artificial delay, close/unmount/late-result guards, no duplicate user entries. | Deployed Network/actual-device/browser observations pending. |
| AC-13 | AnswerServiceTest, ApiTest, SubmissionConcurrencyTest, RateLimitTest: rollback on insert failure, database/Redis errors, unique-tuple-only recovery, one successful commit under concurrency, sanitized failure reporting. | Production log/APM sanitization verification pending. |
| AC-14 | IsolationGuardTest, test bootstrap/base, dedicated compose and full check results: fail-closed hosts/schema/key namespace/credentials/URLs/cache/socket/driver/port/split-connection checks before test connection/mutation; separate MySQL connections stay in owned schema; no destructive shared cleanup. | Manual/external gates remain explicitly pending; overall acceptance is not declared. |

## Limits and risks

No application migration, seeding or purge was run on development/production. The live widget's API requires authorized migration plus exact Origin, dedicated HMAC secret and Redis configuration before functional use. Production operator access defaults denied until explicitly configured for individually authenticated accounts. Session UUID/HMAC is pseudonymous grouping, never visitor authentication. A timeout/unmount can occur after commit; retries deduplicate only while the row exists. Replay answers can change by approved design. After early/retention deletion the same IDs can create a new row. HMAC rotation and backup deletion require the coordinated restricted operational process described in operations.md.

No new dependencies, AI integration, public management routes/dashboard, unrelated UI redesign, commit or push were introduced. Test-only services remain available for review with no published ports; no Docker volume was deleted. Application readiness and production/privacy release are separate records.

## Evidence limitation

A complete pre-change baseline suite run was not captured before feature edits. The original PHPUnit and Vitest regression tests are retained (only explicitly superseded mock-default/copy assertions changed) and pass in the final full suites, but this is post-change regression evidence, not a chronological baseline run. T-01 isolation implementation/red-green checks are complete; its original baseline-record substep cannot be retroactively represented as completed. T-01 remains unchecked for that evidence gap. T-02–T-09 are complete; T-10 automated checks are complete while manual/production/privacy evidence remains pending. There are no remaining failing tests in the final runs.

## 2026-10-09 revalidation after renewed application approval

Re-read AGENTS.md and the four approved canonical Feature 005 documents. The application was already implemented; no application rewrite, requirement change or new dependency was necessary. Earlier ordered red/green evidence remains above rather than being recreated or relabeled as new work.

Before any database/Redis integration test in this revalidation, Sail artisan test --filter=IsolationGuardTest passed: 2 tests, 17 assertions. The complete backend suite then ran through the dedicated isolated Sail test runner with effective-configuration guards, owned random schemas/namespaces and no development/production data access.

| Check | Actual result |
| --- | --- |
| Isolated Sail artisan test | 41 passed, 304 assertions, 64.519 seconds; no failed or skipped tests |
| Sail npm run test:frontend | 49 passed across 8 files; no failures |
| Sail npm run typecheck | Passed, exit 0 |
| Sail npm run build | Passed, exit 0; existing optional fontaine warning remains, no dependency installed |
| Sail php vendor/bin/pint --test | Passed, exit 0 |
| git diff --check / approved ID verification | Passed; all R/AC/T IDs preserved |

Source/diff review confirms unchanged widget.css and ChatTranscript.vue, no dependency manifest/lockfile changes, and only the approved public question POST route. Existing FAQ activation state and collected visitor data were not changed by this revalidation. No development/production migration, purge, Redis flush, volume deletion, commit or push was performed.

Application automated evidence for AC-01–AC-10 and AC-12–AC-14 remains mapped above. This does not close visual/device/screen-reader checks, individual operator infrastructure authorization/audit, deployed Origin/proxy/TLS/ingress/logging checks, scheduler/backup/restore checks or privacy-policy approval (including AC-11). T-01's missing chronological pre-change baseline record remains explicitly unresolved; T-02–T-09 are complete and T-10 automated checks pass while its manual/release evidence stays pending.

The previous local troubleshooting confirmed the user-applied migrations and configured local Origin/HMAC; those observations are separate from isolated automated acceptance. FAQ creation still defaults inactive by approved design and activation requires explicit operator confirmation. No FAQs were activated automatically.
