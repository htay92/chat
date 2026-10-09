# 004 — Ordered TDD tasks

Status: Original UI and avatar amendment authorized and implemented; automated checks complete, manual acceptance partial. Speaker-name separation amendment authorized by the user on 2026-10-09; implementation and automated checks complete, manual verification partial.

Execute only after specification approval. For each change, record the failing acceptance-linked test/check before implementation, the minimum change, green result, and refactor result. Existing green regression tests provide preservation evidence; do not deliberately break application behavior or retain intentional failures. Browser checks are required for CSS properties jsdom cannot prove.

- [x] **T-01 — Establish baseline and evidence map (R-01, R-09 / AC-01, AC-09).** Read Feature 001 documents and current components/styles/tests. Run the five commands below and record counts/exit codes; inspect existing scripts and installed versions before any dependency decision. No dependency additions are planned. Map existing tests to retained behavior and record outstanding Feature 001 manual checks independently. Capture current welcome-page appearance and route/Blade/Vite/CSS/JS baseline for diff comparison. Stop and resolve baseline failures before proceeding.
- [x] **T-02 — Launcher/header structure (R-03, R-06, R-08 / AC-03, AC-06).** First add failing component tests for decorative/non-focusable icons, retained accessible names/IDs, visible Demo badge and unchanged notice. Implement minimal markup in existing components; preserve focus/event contracts. Record browser baseline failure for 56 px circular launcher, then apply styles and measure green dimensions. Refactor with the suites green.
- [x] **T-03 — Theme, bubbles and composer (R-02, R-04, R-06, R-08 / AC-02, AC-04, AC-06).** First add any missing structural tests for visible speaker labels, plain-text/newline rendering and unchanged labeled composer/error semantics. Existing coverage must remain intact. Record pre-change browser failures for palette, alignment and radii; implement namespaced tokens/styles only as needed. Measure rendered colors/geometry and preserve validation, pending and Retry states. Refactor and rerun frontend tests/typecheck.
- [ ] **T-04 — Responsive layout (R-05, R-07 / AC-05, AC-07).** First reproduce and record failing layout checks for required dimensions/gap and any clipping at specified viewports/zoom/keyboard. Implement minimal responsive styles, including short-height fallback. Verify independent transcript scrolling, long Unicode/newline input, validation/Retry visibility and no horizontal page overflow. Recheck open/close/pending focus after layout changes; add missing behavioral tests before any behavioral fix.
- [ ] **T-05 — Accessibility and reduced motion (R-06–R-08 / AC-06–AC-08).** First record any failing contrast, focus, target-size, reduced-motion or actual screen-reader checks; add failing DOM tests for missing semantics before fixes. Verify normal/hover/focus/error/disabled states, keyboard navigation, reopening pending, and unobscured focus. Apply minimum corrections and retain lifecycle/cleanup tests. Do not claim WCAG compliance from DOM tests alone.
- [ ] **T-06 — Final verification and review (all requirements / AC-01–AC-09).** Run all five commands; review diff for preservation and exclusions. Repeat browser checks after final changes. Record automated evidence separately from browser and screen-reader observations. List unperformed checks and retain pending acceptance until verified. Update this task record with actual evidence without silently changing approved requirements. No automatic commit.

## Ordered avatar amendment tasks (after approval)

Original T-01–T-06 records below remain historical evidence for the UI without message avatars. Do not mark new avatar work complete using those results. Run A-01–A-04 in order; retain original pending manual checks.

- [x] **A-01 — Avatar structure, test first (R-01, R-04, R-06, R-08 / AC-04, AC-06, AC-10).** Establish the existing green baseline. Add failing component tests for one role-correct decorative avatar per message, assistant avatar before bubble/user avatar after bubble, retained chronological order and visible speaker labels, aria-hidden wrappers/SVGs and non-focusable icons. Assert no avatar in the empty state and no duplicate user entry/avatar on Retry. Implement minimum ChatTranscript markup using existing role/data; keep provider, validation and API architecture unchanged. Record red, green and refactor results.
- [ ] **A-02 — Avatar styling and responsive fit (R-04, R-05, R-08 / AC-04, AC-05, AC-10).** Before styling, record browser failures for size/colors/alignment/gap. Apply namespaced avatar/row styles: 36 px circles, specified colors, centered 20 px outline icons, top alignment and 8 px gap. Preserve existing launcher/header icon sizes and bubble styling. Measure remaining bubble width at 320×568, 1440×900, 200% zoom and short viewports; test long Unicode/newlines and scrolling. Fix observed overflow with the minimum layout change, then refactor with tests/typecheck green.
- [ ] **A-03 — Accessibility/regressions (R-01, R-06–R-09 / AC-06–AC-10).** Test DOM/log semantics, safe text, visible labels, keyboard/focus, pending/error/Retry, stale responses and disposal. Use an actual screen reader to confirm speaker/message announcements occur without avatar announcements or history repetition. Record icon/background contrast separately from text/control contrast. Add failing tests/checks before any necessary correction; no public failure toggle, new data fields or network calls.
- [ ] **A-04 — Final evidence (all requirements / AC-01–AC-10).** Run the same five Sail commands below. Review diff and record new counts/exit codes plus measured browser evidence/screenshots and actual screen-reader observations. Retain missing checks as pending, and do not overwrite earlier verification history or commit automatically.

## Ordered speaker-name separation tasks (after approval)

Existing T-01–T-06 and A-01–A-04 results remain historical evidence. The new layout is not covered by their completion marks. Run N-01–N-04 in order after approval; keep pending manual checks pending.

- [x] **N-01 — Separate names, test first (R-01, R-04, R-06 / AC-04, AC-06, AC-10, AC-11).** Establish the green baseline. Add failing component tests for exactly one role-correct name outside each bubble, name preceding message in DOM/accessibility order, no name descendant in bubbles and bubble text equal to message.text. Cover both roles, multiline/Unicode/XSS-safe text and Retry without duplicate user name/avatar. Add assertions for hidden/non-focusable avatars and absence of duplicate aria-label/live regions. Implement minimum ChatTranscript markup without changing message data or behavior; record red, green and refactor results. Adapt placement assertions explicitly to the approved structure without weakening their semantic checks.
- [ ] **N-02 — Typography and alignment (R-02, R-04, R-05, R-08 / AC-02, AC-04, AC-05, AC-10, AC-11).** Record pre-style browser failures for external name placement, typography and gap. Add namespaced grid/name styles: default 13 px/600 #475569, 5 px vertical gap, 8 px horizontal gap, avatars at bubble top; assistant left and user right. Keep exact avatar sizes/colors and bubble colors/radii. Measure rendered typography/gaps/alignment at 320×568, 1440×900, 200% zoom and short heights with long/multiline content; correct observed overflow, then refactor with tests/typecheck green.
- [ ] **N-03 — Accessibility and behavior (R-01, R-06–R-09 / AC-06–AC-08, AC-11).** Verify actual screen-reader speech: one name then message, no decorative icon or redundant speaker announcements/history repetition. Verify name contrast at normal-text threshold, DOM/live-region semantics and all existing validation, IME, pending, Retry, focus and cleanup tests. Write failing checks before necessary fixes; do not change chat state, mock responses or Laravel integration.
- [ ] **N-04 — Final verification (all requirements / AC-01–AC-11).** Run all five existing Sail commands below. Record new automated counts/exit codes separately from browser typography/spacing/screenshots and actual screen-reader speech. Review diff for preserved architecture and behavior, retain unavailable manual checks as pending, and do not commit automatically.

## Exact verification commands

Run through Sail from the project root:

```sh
./vendor/bin/sail npm run test:frontend
./vendor/bin/sail npm run typecheck
./vendor/bin/sail npm run build
./vendor/bin/sail artisan test
./vendor/bin/sail php vendor/bin/pint --test
```

Existing scripts: `test:frontend` = `vitest run`; `typecheck` = `vue-tsc --noEmit`; `build` = `vite build`. Do not rename scripts or use host PHP/Composer/Node for project operations. PHP tests remain regression checks, not new chatbot API coverage.

## Evidence record template

Implementation evidence follows. T-04–T-06 remain unchecked because required manual verification is incomplete. The approved requirement/design/acceptance definitions and Feature 001 files were not changed.

For each task record: date, requirement/AC IDs, test file or browser scenario, red command/observation and failure, change summary, green command/measurement, refactor result, and limitations.

For automated verification record exact command, exit code, test/assertion counts where applicable and relevant output/warnings. For manual verification record URL, browser/version, OS/device, CSS viewport, zoom, keyboard/safe-area/reduced-motion settings, screenshot location, measured dimensions/contrast pairs and outcome. Record screen-reader product/version and actual spoken observations separately. Do not copy Feature 001 results as new Feature 004 evidence.


## Actual verification — 2026-10-09

### Automated evidence

All commands used Laravel Sail. Initial baseline passed 29 frontend tests, strict typecheck, build, 2 Laravel tests/2 assertions and Pint. Final results after the focus/style refactor:

| Command | Result |
| --- | --- |
| `./vendor/bin/sail npm run test:frontend` | Exit 0; 6 files, 30 tests passed. |
| `./vendor/bin/sail npm run typecheck` | Exit 0. |
| `./vendor/bin/sail npm run build` | Exit 0; 23 modules built. Existing optional fontaine fallback notice remains; no dependency added. |
| `./vendor/bin/sail artisan test` | Exit 0; 2 tests, 2 assertions passed. |
| `./vendor/bin/sail php vendor/bin/pint --test` | Exit 0; passed. |

Sail PHP 8.5.11 and Composer 2.10.3 are available. Installed versions inspected: Vue 3.5.43, Vite 8.3.4, Laravel Vite plugin 3.2.0, Vue plugin 6.0.9, TypeScript 5.9.3, Vitest 5.0.3. No dependencies or scripts changed.

T-02 red: `tests/frontend/widget.test.ts` gained the 004 AC-03/06 icon/header test before markup changes. Sail frontend run exited 1: unable to get svg in launcher; 29 existing tests still passed. Minimal inline SVG/header badge markup made all 30 tests pass. Retained accessible names, IDs and exact notice are asserted.

T-03: existing tests already cover transcript speaker labels, malicious-looking plain text, internal newlines, label/error associations, validation and failure states, so no redundant tests were added. Pre-theme browser showed dark Send, white transcript and unstyled badge. Namespaced tokens/styles supplied the approved theme and aligned bubbles; tests and typecheck remained green. An intermediate build caught a missing CSS brace; corrected before browser continuation, then build passed.

T-04: before responsive correction, Safari at 320×568 showed the panel's left margin clipped when its page scrollbar reduced usable width. Changed the panel to fixed viewport positioning and percentage width relative to usable layout viewport; rebuilt and confirmed visible margins. Vertical gap remains 12 px (panel bottom 84 px vs launcher top 72 px). Short-height scrolling was added as the specified fallback and verified at 320×300; this fallback was not preceded by a separately captured failing short-height check.

T-05: source inspection exposed the launcher shadow overriding the white focus surround. Moved the focus rule after element styles to retain separation on dark backgrounds; existing focus/lifecycle tests stayed green. This CSS correction was source-review driven, not a new failing automated assertion. Required screen-reader and device checks remain pending.

Diff review: only widget.css, ChatLauncher.vue, ChatPanel.vue and an additive widget test changed in application/test files. Laravel routes, welcome Blade, Vite, app.js, original app.css, provider/types/validation/composable/mount, package files and all existing tests remain unchanged. `git diff --check` passed. No API, AI, persistence, network transport, public failure toggle or commit introduced.

### Browser observations — separate from automated results

Safari on macOS at http://localhost:9000/. Browser version was not re-read during this run. Screenshots are in this session's computer-use tool results, not saved as repository artifacts.

- Baseline welcome content/links remained visible. Initial dark pill launcher did not meet the approved circle/blue theme. After implementation, icon launcher and Demo header rendered with unchanged accessible names and opening focused Message.
- Submitted “Hello, chatbot”: right-aligned blue You bubble followed by slate Assistant bubble containing the exact mock echo. Focus stayed on the panel after completion.
- Responsive mode explicitly set to 320×568: after scrollbar correction, panel/launcher fit with visible margins, wrapped heading, Close, Message and Send reachable. At 320×300, scrolling the panel exposed composer/Send; header remained reachable by scrolling back.
- Responsive mode committed to 1440×900: panel/launcher fit and transcript/header/composer rendered. Safari's 73% preview scaling is distinct from page zoom.
- Actual normal-page zoom confirmed as 200% in Safari Settings for localhost. Controls were visible/reachable with panel scrolling available; no visible horizontal page scrollbar. Restored normal zoom afterward.
- Final build reload reset to closed. Open focused Message; empty Enter showed exact “Please enter a message.” and retained input focus. Final screenshot shows visible focus, rounded composer and unchanged website content.

### Contrast calculations — not rendered-browser measurements

Calculated WCAG relative-luminance ratios from stylesheet colors: white/primary blue 5.17:1; white/hover blue 6.70:1; primary text/white 17.85:1; primary text/assistant slate 16.30:1; secondary text/transcript slate 7.24:1; secondary text/disabled slate 6.15:1; control border/white 4.76:1; error/white 8.31:1; blue icon/assistant slate 4.72:1. Focus blue/white is 6.70:1. These calculations support the palette; they do not replace rendered-state inspection.

### Pending manual acceptance

- Actual screen-reader speech and history-repeat checks (AC-06/08).
- Real mobile virtual keyboard and safe areas (AC-05).
- Browser reduced-motion emulation and host dark-theme observations (AC-02/08).
- Browser computed-style/DOM geometry measurements for exact radii, dimensions, margins, bubble widths and target sizes; current evidence is CSS definitions plus screenshots (AC-02–06).
- New long-transcript scroll run, full browser keyboard navigation/reopening-pending observations, failure/Retry preview, and cleared Network-panel check (AC-01/04/05/07). Corresponding existing automated behavioral tests pass; earlier Feature 001 browser results were not counted as new evidence.

No full WCAG or implementation acceptance sign-off is claimed. Feature 001's outstanding checks remain unchanged. T-04–T-06 require the observations above before completion.

## Avatar amendment evidence

Implementation authorized by the user's “continue implementation?” request. Approved requirement/design/acceptance definitions remain unchanged; their earlier draft labels are superseded by session authorization. A-01 complete; A-02–A-04 retain unchecked status for missing manual evidence. Earlier UI verification is preserved above.

### Test-first and implementation evidence

- Sail baseline: 6 files/30 frontend tests passed, exit 0.
- Added `004 AC-10 avatars follow roles and remain decorative through retry` before implementation. Red run: exit 1, expected one user avatar but found none; all 30 prior tests passed.
- Minimal ChatTranscript markup made all 31 tests pass. Test covers empty-state absence, role-correct DOM placement, chronological labels/newlines, hidden/non-focusable SVGs and wrappers, polite log, and Retry without duplicate user/avatar.
- Built markup before styling and observed Safari failure: unframed icons appeared below bubbles, without circular backgrounds or row alignment. Added namespaced flex rows, non-shrinking 36 px circles, centered 20 px SVGs, exact colors and 8 px gap. Bubble maximum width is min(85%, remaining row width minus 44 px), with min-width:0 and existing safe wrapping. Green browser screenshots show the intended alignment and circles.
- Only ChatTranscript.vue, widget.css and the additive widget test were edited for avatar implementation. Mock responder, message types/data, composable, validation, mount, Laravel APIs/routes, Blade, Vite and dependencies are unchanged. Existing launcher/header SVG sizes remain 24 px; avatar override is scoped to .chat-avatar svg.

### Automated results after avatar styles

| Exact command | Result |
| --- | --- |
| `./vendor/bin/sail npm run test:frontend` | Exit 0; 6 files, 31 tests passed. |
| `./vendor/bin/sail npm run typecheck` | Exit 0. |
| `./vendor/bin/sail npm run build` | Exit 0; 23 modules; existing optional fontaine notice unchanged. |
| `./vendor/bin/sail artisan test` | Exit 0; 2 tests/2 assertions passed. |
| `./vendor/bin/sail php vendor/bin/pint --test` | Exit 0; passed. |

### Avatar browser observations

Safari on macOS, http://localhost:9000/. Browser version not re-read. Screenshots are in this session's computer-use tool results.

- At responsive 1440×900 (73% preview scaling), bot avatar appears left, user avatar right; both are circular and top-aligned with visible labels. Pending disabled composer/Send and focus remained on panel; exact mock echo followed.
- At 320×568 (100% preview, 2x pixel ratio), avatar rows fit usable transcript width with no visible horizontal overflow. Header/composer remain reachable. Avatar circles appear approximately 72 screenshot pixels across (36 CSS px at 2x); precise DOM geometry/colors/gap measurement was not performed.
- Submitted 100 emoji plus an internal newline and “A second line” at narrow width: echo retained newline, wrapped inside the bubble and auto-scrolled to its final line, with header/composer fixed. Escape returned focus to launcher.
- Accessibility tree exposes You/Assistant and message text, with no separate bot/user image entries. This is not actual screen-reader speech evidence.

### Remaining avatar/manual verification

Actual screen-reader speech, exact rendered DOM dimensions/colors/gap, 200% zoom and short-viewport avatar checks, real-device keyboard/safe areas and reduced-motion/host-dark checks remain unperformed in this avatar run. Earlier UI checks are not substitutes for avatar checks. Full acceptance remains pending; no commit made.

## Speaker-name separation evidence

Authorized by the user's “continue implementation.” request. N-01 is complete. N-02–N-04 remain unchecked for required manual evidence; previous results are retained as history. Approved requirement/design/acceptance definitions were not changed during implementation; earlier draft status labels are superseded by this session authorization.

### Red–green evidence — 2026-10-09

Baseline Sail frontend run passed 31 tests. Strengthened the existing avatar/Retry test before markup changes, preserving all avatar assertions and adding name-outside-bubble, exactly one accessible name, exact bubble text/newlines, name-before-message DOM order and no redundant aria-label/live-region assertions. Red run exited 1: bubble still contained strong speaker label; 30 other tests passed. Moving the same name element outside the bubble made all 31 tests pass. No assertion was weakened and no new duplicate speaker label was added.

Built markup before CSS changes. Safari at 320×568 showed the external name beside the bubble rather than above it, establishing the layout failure. Changed only transcript presentation styles to a two-row grid with 13 px/600 slate names, 5 px vertical and 8 px horizontal gaps; role-specific columns keep avatars aligned at bubble top and user rows at the right. Removed the old in-bubble name style and paragraph top margin. Grid width remains bounded with a non-shrinking 36 px avatar; bubbles wrap safely. Existing tests passed after this style/refactor step.

### Automated verification

| Exact command | Result |
| --- | --- |
| `./vendor/bin/sail npm run test:frontend` | Exit 0; 6 files, 31 tests passed, with strengthened AC-10/11 assertions. |
| `./vendor/bin/sail npm run typecheck` | Exit 0. |
| `./vendor/bin/sail npm run build` | Exit 0; 23 modules built. Existing optional fontaine notice unchanged. |
| `./vendor/bin/sail artisan test` | Exit 0; 2 tests/2 assertions passed. |
| `./vendor/bin/sail php vendor/bin/pint --test` | Exit 0; passed. |

Only ChatTranscript.vue, namespaced widget.css and the existing component test were changed for this amendment. State/composable, mock responder, data/types, validation, retry implementation, mount, Laravel routes/Blade/Vite/dependencies remain unchanged. No new live region, aria-label, network operation or commit introduced.

### Browser observations (not screen-reader evidence)

Safari on macOS, http://localhost:9000/; browser version not re-read. Screenshots are in this session's computer-use results.

- At 320×568, Assistant name appears above/outside slate bubble, avatar at bubble top, composer/header reachable. Polite log accessibility tree exposes name followed by message without separate icon entries. Input receives opening focus; valid submission focuses panel and completion retains that focus.
- At 1440×900 (73% preview scaling, not page zoom), both roles are visible together: You right-aligned above blue text-only bubble; Assistant left-aligned above slate text-only bubble. Icons and colors remain as selected.
- Submitted 120 unbroken characters plus newline and emoji text; exact echo returned. At 320×568, content wraps within bubble and transcript can scroll to the final line while header/composer remain reachable. No visible horizontal overflow.
- Name color #475569 against transcript #F8FAFC has the previously calculated 7.24:1 ratio, exceeding normal-text contrast. This is palette evidence, not a new rendered-browser color measurement.

### Pending acceptance

Actual screen-reader speech, precise computed typography/gap/geometry, 200% zoom/short-height checks for this latest structure, real-device virtual keyboard/safe areas and reduced-motion/host-dark observations remain unperformed. Failure/Retry behavior is covered by the strengthened injected-responder test; no production failure toggle added. AC-11/full acceptance and N-02–N-04 remain pending manual evidence. No previous manual limitations were marked complete.
