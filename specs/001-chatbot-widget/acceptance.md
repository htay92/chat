# 001 — Testable acceptance criteria

Status: Draft — awaiting user approval. All criteria are unverified.

| ID | Requirements | Scenario and expected outcome | Evidence |
| --- | --- | --- | --- |
| AC-01 | R-01 | Loading `/` shows exactly one closed widget launcher and preserves the welcome page. Mount code is harmless on pages without its mount element. Existing Vite entries remain in use; no backend API/schema changes exist. | Mount tests, browser, diff review |
| AC-02 | R-02 | Activate launcher: titled panel and demo notice appear. Close button or Escape inside panel closes it; Escape elsewhere does not. Reopen preserves draft/transcript; reload resets both. | Component tests, reload check |
| AC-03 | R-03 | Empty/whitespace-only submission adds nothing and invokes no responder. Valid `  hello  ` submits `hello`; Send and Enter each submit once. Shift+Enter inserts a newline; composing Enter submits nothing. | Validation and component tests |
| AC-04 | R-03 | Trimmed input containing 1,000 code points, including emoji, is accepted; 1,001 is rejected with an associated visible error. Rejection preserves the draft and invokes no responder; editing clears validation feedback. | Boundary/component tests |
| AC-05 | R-04 | Empty transcript shows instructions. Sending a valid message appends one labeled user entry and clears the draft. Success appends one labeled assistant entry in order; the latest entry is visible. `<img src=x onerror=alert(1)>` appears as text, creates no image, and runs no code. | Component tests and browser scroll check |
| AC-06 | R-05 | At 499 ms no reply exists; advancing to 500 ms resolves exactly `Mock reply: You said: hello` for `hello`. No chat request uses fetch/XHR or another network transport. | Fake timers, spies, source review, browser Network panel |
| AC-07 | R-06 | During pending, editing/Send are disabled and “Replying…” is announced. Rapid clicks/Enter invoke the responder once. Closing during pending permits completion; reopening shows the reply. | State/component tests |
| AC-08 | R-06 | Inject rejection: original user entry remains, no assistant entry appears, exact failure text is visible, and Retry is enabled. Retry invokes the failed input once without adding a duplicate user entry; success clears the error. A new valid submission before retry dismisses the previous retry state. | Injected responder tests |
| AC-09 | R-06 | Unmount during pending clears the default mock timer. A late injected success/rejection causes no state update or unhandled rejection after disposal. | Cleanup/state tests |
| AC-10 | R-08 | Launcher exposes name, expanded state, and panel reference. Opening focuses input, or close when input is disabled; closing returns focus to launcher. Tab can reach the page outside the non-modal panel. Hidden controls cannot receive focus. Replies never steal focus. | Component tests and keyboard browser check |
| AC-11 | R-08 | Input/dialog have accessible labels; log announces new messages politely with speaker identity without rereading history. Pending uses status; failure uses alert; validation references its field. Text/UI contrast meets stated thresholds, focus is visible, and specified controls are at least 44×44 px. | DOM tests, contrast measurement, screen-reader/browser check |
| AC-12 | R-07 | At 320×568 and 1440×900 px, launcher/panel stay within the viewport, panel is at most 380 px wide, transcript scrolls while composer/header remain reachable, and no horizontal page overflow is introduced. At 200% zoom all controls remain usable. Safe-area offsets apply and reduced-motion disables optional transitions. | Recorded viewport, zoom, safe-area and reduced-motion checks |
| AC-13 | R-09 | Acceptance-linked frontend tests, strict typecheck, frontend build, existing PHPUnit tests, and Pint check pass. Record browser evidence for AC-01/02/05/06/10/11/12. No Laravel API/OpenAI integration, chat persistence, or Compose changes were introduced. | Command results, manual evidence, diff review |

## Review and sign-off

Specification approval: pending user review. Implementation acceptance: not started. Approval of this draft does not mark any criterion passed. Record each criterion's automated test reference or manual observation during implementation; explain any blocked verification rather than claiming completion.
