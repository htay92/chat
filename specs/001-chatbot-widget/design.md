# 001 — Chatbot widget design

Status: Draft — awaiting user approval.

## Existing configuration and planned integration

Laravel serves `welcome.blade.php` at `/`. `vite.config.js` already configures Laravel CSS/JS entry points and Tailwind; retain both entries and add the Vue plugin during approved implementation. Keep `resources/js/app.js` as a thin mount entry importing the TypeScript widget module. Add a dedicated Blade mount element without replacing the welcome page. No routes, controllers, services, migrations, Compose changes, or API credentials are needed.

Planned development dependencies: Vue 3, `@vitejs/plugin-vue`, TypeScript, `vue-tsc`, Vitest, `@vue/test-utils`, and jsdom. Select versions compatible with installed Vite 8 and Sail's Node runtime, and update the npm lockfile during implementation. Add a strict frontend tsconfig and separate Vitest configuration that uses the Vue plugin and jsdom without Laravel font fetching/build side effects. Tests must not require MySQL, Redis, or internet access.

## Component boundaries (proposed paths)

| Module | Responsibility | Interface |
| --- | --- | --- |
| `resources/js/components/chatbot/ChatbotWidget.vue` | Own visibility, wire state and focus, fixed positioning | Optional typed responder prop; defaults to mock |
| `ChatLauncher.vue` in the same directory | Native launcher button | `expanded`, `panelId`; emits `open` |
| `ChatPanel.vue` in the same directory | Non-modal dialog, heading, notice, close and status/error UI | Transcript/pending/error props; emits close/send/retry |
| `ChatTranscript.vue` in the same directory | Empty state, speaker-labeled text messages, scrolling | `messages`; stable message keys |
| `ChatComposer.vue` in the same directory | Draft input, validation presentation, keyboard handling | Draft/pending/error props; emits draft update/submit |
| `resources/js/composables/useChatbot.ts` | Validate input and manage exchange lifecycle | `submit`, `retry`, state refs, disposal |
| `resources/js/chatbot/mockResponder.ts` | Cancellable 500 ms local reply | Implements responder contract |
| `resources/js/chatbot/types.ts` | Shared types | Message and responder definitions |

Keep validation/state ownership in the composable so all submission paths share the same rules. Components use `<script setup lang="ts">` and Vue text interpolation; never `v-html`.

## Data and state

`ChatMessage` has a stable local ID, role (`user` or `assistant`), and text. IDs use a page-local monotonic sequence; timestamps are unnecessary. `ChatResponder` accepts `(message: string, signal: AbortSignal)` and returns `Promise<string>`. The default responder does no I/O and clears its timeout on abort.

Visibility (`isOpen`) is independent of exchange state (`idle`, `pending`, `failed`). Draft, transcript, validation error, and optional failed input live in memory. No browser storage or cookies are introduced.

1. Submit: guard pending; trim and count with `Array.from(text).length`; on invalid input retain draft and set error. On valid input clear errors/failed input, append user message, clear draft, and enter pending.
2. Success: if still mounted and operation ID is current, append assistant message and return to idle. Announce and scroll, without stealing focus.
3. Failure: ignore abort from disposal; otherwise retain transcript, store failed input, expose error, and enter failed.
4. Retry: available only in failed state; call responder with failed input, enter pending, and do not append another user message.
5. Close/reopen: alter visibility only. Keep panel mounted but hidden while closed so pending operations continue. Restore launcher focus on close and input focus on reopen (or the close button if pending makes input disabled).
6. Unmount: abort the current operation, clear mock timer, and invalidate its operation ID. Never update disposed state.

On submission, move focus from a control that will be disabled to a stable panel element. Completion does not move focus; the re-enabled input remains reachable by Tab. If focus has moved outside the panel, completion must not pull it back. Clear validation on draft edits; errors remain associated with the field until cleared.

## Accessibility and layout

Use `role="dialog"` with `aria-labelledby`, without `aria-modal="true"`. Tab follows ordinary page order; Escape only applies inside the panel. The launcher references a stable panel ID and has an explicit accessible name. A labeled textarea supports multiline and IME input. Use a polite message log with speaker labels; do not announce the entire transcript repeatedly. Separate status/alert regions handle pending/error feedback. Hidden panel content is absent from keyboard navigation and the accessibility tree.

Use existing Tailwind styles plus scoped styles where needed. Fix the widget bottom-right with 16 px spacing plus safe-area offsets; panel width `min(380px, calc(100vw - 32px))` and height bounded using dynamic viewport units. Header/composer do not shrink; transcript uses `min-height: 0` and vertical overflow. Provide visible focus and compliant contrast. Avoid required animation; optional transitions respect `prefers-reduced-motion`.

## Test strategy and limits

Vitest/jsdom verifies composable transitions and component DOM behavior under fake timers. Test teardown unmounts components and restores timers. Mock rejection tests exercise Retry; delayed completion tests exercise close and disposal. Assert malicious-looking text creates no elements and neither fetch nor XMLHttpRequest is invoked by chat interactions.

Browser inspection covers rendering dimensions, zoom, contrast, keyboard navigation, actual live-region announcements, scroll behavior, and Network-panel chat traffic. jsdom alone cannot prove these. Existing PHPUnit tests remain regression checks; no backend chatbot tests are needed for this feature. Frontend build/font tooling may fetch assets; the no-network requirement applies to chat interactions, not unrelated existing build tooling.
