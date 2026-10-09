# 001 — Chatbot widget requirements

Status: Draft — awaiting user approval. No implementation authorized.

## Purpose and scope

Provide a floating Vue 3 chatbot on the existing Laravel welcome page (`/`). Use the Composition API and TypeScript. Responses are deterministic local mocks, clearly labeled as a demo.

Excluded: Laravel API endpoints, OpenAI or other AI integrations, network requests for chat, authentication, database persistence, Redis usage, streaming, Markdown/HTML message rendering, attachments, conversation export, and multi-page integration. MySQL and Redis configuration remains unchanged.

## Numbered requirements

1. **R-01 — Integration:** Mount exactly one widget on `/` through the existing Vite CSS/JS entries. Preserve the welcome page and current Laravel route. No backend API or database changes.
2. **R-02 — Visibility:** Initially show a bottom-right launcher labeled “Open chat” with the panel closed. Activation opens a panel titled “Demo chatbot”, containing a persistent notice “Demo only — responses are mocked.” A close button or Escape while focus is within the panel closes it. Reopening preserves conversation and draft for the current page lifetime; reload resets them.
3. **R-03 — Input:** Provide a labeled multiline message field and Send button. Trim leading/trailing whitespace; reject empty or whitespace-only input without adding a message. Allow at most 1,000 Unicode code points in the trimmed message. Longer input shows an associated validation error and preserves the draft. Send and Enter submit valid input; Shift+Enter inserts a newline. Enter during IME composition must not submit. Rejected input must never invoke the responder.
4. **R-04 — Transcript:** Start with an empty transcript and an instructional empty state. Each valid submission appends one user message, clears the draft, and shows pending feedback. Append exactly one assistant reply on success, preserving chronological order and speaker labels. Render all message content as plain text, including HTML-like input. After submission/completion, scroll the transcript to the latest message.
5. **R-05 — Mock behavior:** A local responder resolves after 500 ms with `Mock reply: You said: {message}`. `{message}` is the validated, trimmed input. Production widget behavior makes no chat network requests. A typed injected responder permits deterministic success/failure tests without adding a user-visible failure trigger.
6. **R-06 — Pending and failure:** Allow only one request at a time. While pending, disable editing and submission, expose “Replying…” feedback, and prevent duplicate submissions. On rejection, retain the user message, add no assistant reply, show “Could not generate a mock reply. Please try again.”, and enable Retry. Retry uses the same failed message without duplicating it. Before retry, a new valid submission dismisses the old retry state and starts a new exchange. On success clear the error. Closing does not cancel a pending reply; unmount cancels local mock timers and ignores late completions.
7. **R-07 — Responsive layout:** Keep the launcher and panel within the viewport at 320×568 and 1440×900 CSS pixels. Panel width is at most 380 px and fits smaller screens with 16 px side margins; height is bounded by available viewport height. Scroll the transcript independently while header/composer remain accessible. Respect safe-area insets and reduced-motion preferences. At 200% zoom controls remain reachable without horizontal page scrolling.
8. **R-08 — Accessibility:** Use native buttons/form controls, visible focus indicators, a labeled non-modal dialog, and launcher `aria-expanded`/`aria-controls`. Opening focuses the input; closing returns focus to the launcher. Do not trap focus or mark the page inert. Announce new transcript entries politely with speaker identity; pending feedback uses a status region and failure uses an alert. Validation errors are associated with the input. Do not move focus when replies arrive. Meet WCAG 2.2 AA contrast thresholds (4.5:1 normal text, 3:1 large text and relevant UI indicators); launcher/close/send/retry targets are at least 44×44 CSS px.
9. **R-09 — Verification:** Add acceptance-linked Vitest tests for state, validation, mock timing, keyboard/focus behavior, pending/error/retry, safe text rendering, and cleanup. Use fake timers and injected responders. Add strict TypeScript checking and preserve existing PHPUnit coverage. Record browser checks for layout, zoom, contrast, screen-reader announcements, and no chat network traffic.

## Approval

Approve these requirements together with [design](design.md), [tasks](tasks.md), and [acceptance criteria](acceptance.md) before implementation. Numeric limits, wording, mounting scope, and mock timing above are proposed product decisions for this review.
