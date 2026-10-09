# 001 — Ordered TDD tasks

Status: Implementation authorized by the user in this session. T-01–T-07 complete; T-08 automated verification complete, manual acceptance partially verified.

Follow this order after approval. For each behavior task, record the failing test, minimum implementation, passing result, and refactor result. Do not change scope silently.

- [x] **T-01 — Frontend test foundation (R-01, R-09 / AC-01, AC-13).** Add compatible Vue/TypeScript/Vitest dependencies, strict tsconfig, isolated jsdom/Vue test configuration, and scripts `test:frontend` (`vitest run`) and `typecheck` (`vue-tsc --noEmit`). Preserve existing Vite entries and Tailwind/Laravel plugins. Prove the harness detects a deliberately failing assertion before adding a minimal passing harness test; record evidence rather than retaining intentional failures.
- [x] **T-02 — Validation and types (R-03 / AC-03, AC-04).** Write failing tests for blank input, trimming, exact 1,000/1,001 code-point boundaries (including emoji), and preserved invalid draft. Implement shared types and composable validation.
- [x] **T-03 — Local responder (R-05 / AC-06).** Write fake-timer tests for no early resolution, exact response after 500 ms, and abort cleanup. Implement cancellable mock responder; prove chat operations make no network calls.
- [x] **T-04 — Exchange lifecycle (R-04, R-06 / AC-05, AC-07, AC-08, AC-09).** Write tests for append/clear/pending/success, double submission, rejection, retry without duplicate user messages, new submission after failure, and ignored late completion on disposal. Implement state transitions and injected responder support.
- [x] **T-05 — Launcher and dialog (R-01, R-02, R-08 / AC-01, AC-02, AC-10).** Write component tests for initial closure, opening, notice, close/Escape, state preservation, focus return, and accessible names/expanded state. Implement widget/launcher/panel, including pending-open focus fallback.
- [x] **T-06 — Transcript and composer (R-03, R-04, R-08 / AC-03, AC-04, AC-05, AC-10, AC-11).** Write tests for Enter, Shift+Enter, IME composition, labels/error association, disabled pending controls, safe text rendering, speaker identity, and status/alert semantics. Implement components and verify completion does not steal focus.
- [x] **T-07 — Page integration and responsive behavior (R-01, R-07 / AC-01, AC-12).** Write mount tests proving exactly one widget mounts when the element exists and no failure occurs when it is absent. Add the welcome-page mount element and import through the existing JS entry. Implement viewport/safe-area styles and reduced-motion behavior; manually verify dimensions, zoom, scroll, and focus.
- [ ] **T-08 — Acceptance verification (all requirements / AC-01–AC-13).** Run frontend tests, typecheck, build, existing PHPUnit tests, and formatting checks. Perform browser/screen-reader/network checks listed in acceptance.md. Record actual outcomes and resolve failures before declaring acceptance complete. No chatbot API, OpenAI calls, database migrations, or automatic commits.

## Planned commands (after approval and setup)

```sh
./vendor/bin/sail npm run test:frontend
./vendor/bin/sail npm run typecheck
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test
./vendor/bin/sail php vendor/bin/pint --test
```

Use `./vendor/bin/sail npm` for dependency setup as well. These commands are not executed by specification creation. Tests/builds may write caches or artifacts during later approved implementation. Do not run destructive database commands.

## Verification record

Verified on 2026-10-09. The user selected the current specification files as the implementation baseline and explicitly authorized implementation. Requirements, design, and acceptance definitions were not edited. The pre-existing AGENTS.md and untracked specs/README.md changes were not modified.

### Test-first evidence

All commands below ran through Laravel Sail. New behavioral suites were written before their corresponding implementation; missing-module failures were followed by passing assertions once the modules existed. No intentional failures remain.

| Task | Red evidence | Green evidence / refactor |
| --- | --- | --- |
| T-01 | Vue harness assertion failed: expected true to be false (exit 1). Initial typecheck also revealed latest TypeScript/vue-tsc incompatibility. | Harness corrected to its expected true value; compatible TypeScript ~5.9.3 selected. Tests, typecheck, and existing build passed before T-02. Node 24.21.0, Vite 8.3.4, Laravel Vite plugin 3.2.0 inspected before dependency selection. Registry engine/peer metadata inspected for Vue plugin, Vitest, and jsdom. |
| T-02 | Validation suite failed because validation.ts did not exist. | Six validation tests passed after implementation and correction of one assertion typo. Covers whitespace/newline-only input, trimming, internal newlines, emoji/combining marks, and 1,000/1,001 code-point boundaries. |
| T-03 | Mock suite failed because mockResponder.ts did not exist. | Three mock tests passed; cumulative suite: 10 tests. Exact 499/500 ms timing, fixed approved echo, fetch/XHR spies, abort-before-start and timer cleanup. T-03 implementation began after T-02 passed. |
| T-04 | Exchange suite failed because useChatbot.ts did not exist. | Eight lifecycle tests passed; cumulative suite: 18. Failure/retry, duplicate suppression, new input after failure, synchronous throws, late success/rejection after disposal, aborted signals, and timer disposal. |
| T-05 | Widget suite failed because ChatbotWidget.vue did not exist. | Four shell tests passed; cumulative suite: 22. Open/close/Escape, draft/reset behavior, accessible dialog/launcher, panel focus before disabling, and pending-reopen Close focus. |
| T-06 | Five additional component tests failed for missing Enter submission, validation association, empty/log transcript, Retry transcript behavior, and timer evidence. | All nine widget tests passed after composer/transcript implementation; cumulative suite: 27. The integrated cleanup assertion was corrected to observe clearTimeout, avoiding unrelated jsdom timers. Extracted panel/composer/transcript components retained green tests and typecheck. |
| T-07 | Mount suite failed because mount.ts did not exist. | Two mount tests passed; cumulative suite: 29. Missing element is harmless; repeated mount does not duplicate widget or alter existing link/content. Build and typecheck passed. Manual 200% zoom initially exposed composer overflow; short-viewport spacing corrected and verified visually after rebuilding. |
| T-08 | No deliberately failing test introduced: this is the final acceptance gate. | All automated commands below passed. Remaining manual checks keep this task unchecked. |

### Automated results

| Exact command | Actual result |
| --- | --- |
| `./vendor/bin/sail npm run test:frontend` | Exit 0; 6 files, 29 tests passed after final layout fix. |
| `./vendor/bin/sail npm run typecheck` | Exit 0; strict Vue/TypeScript check passed with TypeScript 5.9.3. |
| `./vendor/bin/sail npm run build` | Exit 0; 23 modules built. Both original Vite entry paths retained. Existing original CSS output remained app-Culf9leE.css; widget CSS emitted separately. |
| `./vendor/bin/sail artisan test` | Exit 0; 2 PHPUnit tests, 2 assertions passed, including welcome response. |
| `./vendor/bin/sail php vendor/bin/pint --test` | Exit 0; passed. |
| `./vendor/bin/sail artisan route:list --json` | Exit 0; existing welcome, storage, and health routes; no chat routes. |

`test:frontend` is `vitest run`; `typecheck` is `vue-tsc --noEmit`. Dependency installation and checks used Sail. No dependency audit vulnerabilities were reported at install time. Build prints an existing optional fontaine/font fallback notice; the build succeeds and existing font configuration was preserved.

Diff review confirmed welcome Blade bytes match the baseline after removing the single added mount element. Existing app.js content is retained with an additive mount import/call. Routes, backend files, original CSS, Compose, and approved requirements/design/acceptance files are unchanged. New CSS is namespaced under .chatbot-widget. Git whitespace review of changed application files passes; the existing AGENTS.md EOF warning belongs to a pre-existing user change.

### Browser observations (not screen-reader evidence)

URL: http://localhost:9000/. Safari 27.0.1 on macOS, exercised through native computer-use controls. Screenshots and accessibility-tree observations are available in the session tool history.

- AC-01/02: existing welcome content and links visible; single initially closed launcher. Opening presents demo notice and focuses Message. Escape hides the panel and returns focus to launcher. Reload resets the conversation. Chrome 154.0.8037.98 also exposed the launcher/dialog/input correctly, but its screenshots did not reliably reflect the action state; visual evidence uses Safari.
- AC-05/07/10: Enter produced the exact local echo. Pending disabled Message/Send and focused the panel; completion re-enabled controls without moving focus. A pasted 15-line message and echo scrolled to the final content while header/composer stayed fixed.
- AC-10: Tab moved from panel to Conversation, Message, and then outside the widget to the page. Safari Option+Tab reached the existing Documentation link; reverse Option+Shift+Tab reached browser controls. No focus trap observed.
- AC-06: cleared Safari Web Inspector Network items, submitted “network check”, and observed 0 resources/0 domains through completed echo. No chat network request appeared.
- AC-12: responsive design mode set to 320×568 and 1440×900 CSS px. Panel/launcher fit visibly; Close, Message, and Send stayed reachable, with no visible horizontal page scrollbar. Desktop responsive preview scaled to 73% to fit the display; that is preview scaling, not page zoom.
- AC-12: normal page zoom explicitly confirmed as 200% in Safari's “Settings for localhost” popover. The short-viewport fix kept controls inside the panel; no visible horizontal overflow. Restored normal zoom afterward.

### Contrast and control evidence

Calculated WCAG relative-luminance ratios from explicit stylesheet colors: #172033/#ffffff 16.27:1; #475569/#ffffff 7.58:1; #172033/#f1f5f9 14.85:1; #172033/#e0e7ff 13.20:1; #991b1b/#ffffff 8.31:1; #ffffff/#334155 10.35:1; #64748b/#ffffff 4.76:1; #1d4ed8/#ffffff 6.70:1. These exceed the specified text/UI thresholds. Focus uses a blue outline plus white surround; launcher border remains distinct on dark host pages. Styles require all launcher/close/send/retry buttons to be at least 44×44 CSS px; screenshots confirm usable sizing, but no rendered DOM geometry measurement was available.

### Unperformed acceptance checks / limitations

- Real screen-reader announcements (AC-11): not performed. Accessibility-tree and live-region DOM checks do not establish actual spoken announcements or whether history is reread.
- Real-device safe-area insets and browser reduced-motion emulation (AC-12): not performed. CSS supports both; no required animation is introduced.
- Mobile virtual keyboard: not exercised; it is not an additional requirement in the selected current specification files.
- Browser rejection/Retry UI: verified with injected responders in Vitest; the default mock deliberately has no user-facing failure trigger.

T-08 and full acceptance sign-off remain pending these manual observations. No tests or checks are claimed passed without the evidence above. No API, persistence, external AI, destructive database operation, or automatic commit was introduced.
