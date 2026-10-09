# 001 — Ordered TDD tasks

Status: Draft — awaiting user approval. All tasks below are future implementation work; none is complete.

Follow this order after approval. For each behavior task, record the failing test, minimum implementation, passing result, and refactor result. Do not change scope silently.

- [ ] **T-01 — Frontend test foundation (R-01, R-09 / AC-01, AC-13).** Add compatible Vue/TypeScript/Vitest dependencies, strict tsconfig, isolated jsdom/Vue test configuration, and scripts `test:frontend` (`vitest run`) and `typecheck` (`vue-tsc --noEmit`). Preserve existing Vite entries and Tailwind/Laravel plugins. Prove the harness detects a deliberately failing assertion before adding a minimal passing harness test; record evidence rather than retaining intentional failures.
- [ ] **T-02 — Validation and types (R-03 / AC-03, AC-04).** Write failing tests for blank input, trimming, exact 1,000/1,001 code-point boundaries (including emoji), and preserved invalid draft. Implement shared types and composable validation.
- [ ] **T-03 — Local responder (R-05 / AC-06).** Write fake-timer tests for no early resolution, exact response after 500 ms, and abort cleanup. Implement cancellable mock responder; prove chat operations make no network calls.
- [ ] **T-04 — Exchange lifecycle (R-04, R-06 / AC-05, AC-07, AC-08, AC-09).** Write tests for append/clear/pending/success, double submission, rejection, retry without duplicate user messages, new submission after failure, and ignored late completion on disposal. Implement state transitions and injected responder support.
- [ ] **T-05 — Launcher and dialog (R-01, R-02, R-08 / AC-01, AC-02, AC-10).** Write component tests for initial closure, opening, notice, close/Escape, state preservation, focus return, and accessible names/expanded state. Implement widget/launcher/panel, including pending-open focus fallback.
- [ ] **T-06 — Transcript and composer (R-03, R-04, R-08 / AC-03, AC-04, AC-05, AC-10, AC-11).** Write tests for Enter, Shift+Enter, IME composition, labels/error association, disabled pending controls, safe text rendering, speaker identity, and status/alert semantics. Implement components and verify completion does not steal focus.
- [ ] **T-07 — Page integration and responsive behavior (R-01, R-07 / AC-01, AC-12).** Write mount tests proving exactly one widget mounts when the element exists and no failure occurs when it is absent. Add the welcome-page mount element and import through the existing JS entry. Implement viewport/safe-area styles and reduced-motion behavior; manually verify dimensions, zoom, scroll, and focus.
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

Not run — documentation-only draft. Add command results, acceptance IDs, browser viewport/zoom results, assistive-technology observations, and unresolved issues during implementation. User approval: pending.
