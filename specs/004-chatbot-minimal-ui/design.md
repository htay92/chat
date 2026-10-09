# 004 — Minimal SaaS chatbot UI design

Status: Original UI and avatar amendment authorized and implemented. Speaker-name separation amendment is draft — awaiting review. Presentation change only; no implementation of this amendment authorized.

## Existing architecture

Keep `resources/js/components/chatbot/{ChatbotWidget,ChatLauncher,ChatPanel,ChatTranscript,ChatComposer}.vue`, `resources/js/composables/useChatbot.ts`, and `resources/js/chatbot/{types,validation,mockResponder,mount}.ts` in place. Widget imports `resources/js/chatbot/widget.css`; extend its namespaced selectors and local custom properties. Keep Vue Composition API/TypeScript and existing props/events. Do not relocate components or alter state ownership.

`ChatResponder = (message: string, signal: AbortSignal) => Promise<string>` remains injected and replaceable for a separately approved future provider. The default mock still waits 500 ms and returns `Mock reply: You said: {trimmed message}`. It does no I/O. This temporary echo is for frontend testing/demo use; dummy Q&A is separate scope.

Keep the existing welcome Blade mount, app.js mount call, CSS/JS Vite entries, Laravel/Vue/Tailwind plugins and Instrument Sans configuration. No backend, dependency, route, database, Compose or general website styling changes.

## Visual tokens (R-02–R-04, R-06, R-08)

| Token / element | Proposed value |
| --- | --- |
| Primary / hover | `#2563EB` / `#1D4ED8` |
| Panel, header, composer | `#FFFFFF` |
| Transcript / assistant bubble | `#F8FAFC` / `#F1F5F9` |
| Primary / secondary text | `#0F172A` / `#475569` |
| User bubble text/background | `#FFFFFF` / `#2563EB` |
| Decorative separator/border | `#E2E8F0` |
| Required textarea/control boundary | `#64748B` |
| Error text | Existing `#991B1B` |
| Focus | 3 px `#1D4ED8` outline, 3 px offset, white surrounding separation |
| Panel geometry | Max 380 px border-box width, 20 px radius, max preferred height 540 px |
| Launcher | Exactly 56 px border-box diameter, 50% radius, centered 24 px icon |
| Bubble / textarea radius | 16 px / 14 px |
| Message avatar | 36×36 px border-box, 50% radius, non-shrinking |
| Assistant avatar background / icon | #DBEAFE / #2563EB |
| User avatar background / icon | #E2E8F0 / #334155 |
| Avatar icon | Centered 20×20 px local outline SVG; 24×24 viewBox, 2 px stroke, rounded caps/joins |
| Avatar / bubble gap | 8 px horizontal; avatar aligned to bubble top |
| Speaker name | 13 px, font-weight 600, color #475569; outside bubble |
| Name / bubble gap | 5 px vertical (allowed range 4–6 px) |
| Panel shadow | `0 12px 32px rgb(15 23 42 / 12%)` |
| Launcher shadow | `0 6px 20px rgb(15 23 42 / 16%)` |

Use inherited font and current readable base size (16 px/1.5). Heading remains 18 px bold; small labels/notice must satisfy normal-text contrast. Apply blue only where specified; failure remains explicit text rather than color alone. Disabled composer uses slate fill and clear native disabled semantics. Verify actual rendered combinations, including hover/focus/disabled states; palette selection alone is not accessibility evidence.

## Component presentation

- **ChatLauncher:** replace visible “Chat” with a local inline SVG chat icon; retain native button, accessible name, aria-expanded/controls and event. SVG uses `aria-hidden="true"` and `focusable="false"`.
- **ChatPanel:** add decorative assistant icon and visible Demo badge beside the existing heading; preserve its heading ID/dialog reference and notice. Keep Close accessible and ≥44 px in both dimensions. Header may wrap at narrow widths without clipping.
- **ChatTranscript:** keep the chronological ordered list and stable message keys. Each list item contains a decorative avatar, a separate speaker-name element and a message bubble containing only interpolated message text. Assistant DOM order is avatar, name, bubble; user DOM order is name, bubble, avatar. Hidden avatars do not affect accessible reading order: name precedes its message exactly once. Use a two-column, two-row grid: assistant avatar in column 1/row 2, name in column 2/row 1, bubble in column 2/row 2; user name in column 1/row 1, bubble in column 1/row 2, avatar in column 2/row 2. Row gap is 5 px and column gap is 8 px. Avatar is fixed/non-shrinking 36 px and aligned to the bubble top. Size the name/bubble column to fit content, capped at 85% of transcript content width and available width minus 44 px for avatar/gap; allow min-width:0 and safe wrapping. Assistant row/name/bubble aligns left; user row/name/bubble aligns right. Apply existing bubble color/radius/padding only to the message bubble; name is 13 px semibold #475569 on the transcript background, not white-on-blue. Retain pre-wrap, overflow-wrap:anywhere and plain-text interpolation. Derive name/icon from message.role; no new message fields, avatar URLs, state or event changes.
- **ChatComposer:** retain native form, Message label, multiline textarea, Send and associated validation text. Round textarea and button, keeping error/status/Retry outside disabled controls. Preserve Shift+Enter, Enter, IME guard and all existing exact messages.
- **ChatbotWidget/composable:** retain visibility/state transitions and focus logic. No new behavior or provider parameters.

## Message avatar amendment (R-01, R-04–R-06, R-08–R-09)

Use simple Lucide-style bot and user silhouettes drawn as local inline outline SVGs, without adding lucide-vue-next or any icon dependency. Bot silhouette uses a rounded robot head/antenna; user silhouette uses a head circle and shoulder outline. Both icons inherit their specified icon color through currentColor. Keep launcher/header icons and their sizes unchanged; avatar-specific selectors must override the existing general 24 px SVG size to 20 px.

Avatar wrapper uses aria-hidden="true"; each SVG also uses aria-hidden="true" and focusable="false". No avatar title, role=img, accessible name, tabindex, click handler or tooltip is needed. Speaker identity continues to come from the visible label and existing polite log. Avatar insertion must not announce an extra “bot”/“user”, duplicate messages or reread history. Empty/pending/error UI has no message avatar unless an actual message entry exists. Retry must not create another avatar/user entry; the successful reply gets its own assistant avatar.

At narrow widths and zoom, keep avatars 36 px, horizontal gap 8 px and name-to-bubble gap 5 px; shrink/wrap the name/bubble column within available width instead. Test both short and multiline/long unbroken Unicode content; no horizontal overflow or clipping, and transcript auto-scroll remains intact. Decoration has no 44 px target-size obligation. Record avatar icon/background contrast as visual evidence without replacing text/control requirements or relying on avatars alone for speaker identity.

## Speaker-name separation amendment (R-01, R-04–R-06, R-09)

Move the existing visible speaker label out of the colored bubble without adding a second label. Names remain ordinary accessible text, not headings or interactive controls. Do not add a redundant aria-label to the row/bubble or another live region; retain the existing polite log, additions-only announcements and speaker-first reading sequence. Decorative avatar wrappers/SVGs stay aria-hidden and non-focusable. Confirm actual screen-reader announcements after restructuring; DOM tests alone cannot prove that a name/message is spoken exactly once.

Bubble text must equal message.text, including internal newlines and HTML-like characters rendered safely as text. Names must never be concatenated into message.text or responder inputs. Preserve colors, 16 px bubble radii, avatars, chronological order, auto-scroll, keyboard/focus, validation, pending/error/retry and disposal. A Retry keeps the failed user entry with one name/avatar, then adds an assistant entry on success. No chat state, mock, data/type, validation, API or Laravel integration changes.

## Responsive and motion design (R-05–R-08)

Widget sits bottom-right at 16 px plus right/bottom safe areas. Panel bottom offset is 68 px (56 px launcher + 12 px gap). Width is bounded by 380 px and viewport width minus 32 px and horizontal safe areas. Height is bounded by 540 px and dynamic available viewport height minus launcher, gap, top/bottom margins and safe areas. Use dynamic viewport units through the existing styling infrastructure.

Header/composer normally do not shrink; transcript has min-height:0 and overflow-y:auto. Retain compact short-viewport treatment, recalculating for the larger launcher. When keyboard/zoom makes fixed sections taller than available space, allow vertical panel scrolling so Close, validation, Retry and composer remain reachable. Avoid fixed minimum dimensions that force horizontal overflow. Focused composer and Send must remain visible above an open mobile keyboard. Verify actual device behavior; add viewport handling only if evidence shows CSS is insufficient, without changing chat state.

Retain non-modal navigation and no inert page/focus trap. Open focuses Message unless pending, then Close. Valid submit/Retry moves focus to the stable panel before disabling controls; invalid input remains focused. Close restores launcher focus, and completion never moves focus. Closing during pending keeps the operation alive. Disposal cancels the mock and invalidates current operation; late success/rejection must neither mutate state nor become unhandled.

Only optional 120 ms color/box-shadow transitions; reduced-motion disables transitions, animations and smooth scrolling. No animated entry or pulsing indicator.

## Verification design

Use existing Vitest/jsdom suites for structure, decorative icon semantics, visible labels and behavioral regressions. Do not assert computed geometry using jsdom or weaken existing tests to match styling. Browser measurements cover exact dimensions, colors, contrast, wrapping, viewport bounds, focus, zoom and keyboard. An actual screen reader covers new-message announcements without history repetition. Separate failure/retry observations use an injected test responder, never a public failure toggle. See [acceptance](acceptance.md) for evidence requirements and [tasks](tasks.md) for the red–green sequence.
