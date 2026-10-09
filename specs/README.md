# Spec-Driven Development

Feature specifications are the source of truth for implementation. Follow the repository's `AGENTS.md`; use Laravel Sail for PHP, Composer, Artisan, and npm commands. Do not commit or push automatically.

## Workflow

1. Create a numbered feature directory, such as `001-chatbot-widget/`.
2. Write `requirements.md` with numbered behavior and scope boundaries.
3. Write `design.md` with architecture, state, interfaces, accessibility, and test strategy.
4. Write `acceptance.md` with observable criteria linked to requirement IDs.
5. Write `tasks.md` with ordered, unchecked TDD steps and verification commands.
6. Submit all four documents for user approval. Draft specifications authorize no implementation. Record approval only after it is explicitly given; do not infer it from elapsed time.
7. After approval, implement one task at a time: write a failing acceptance-linked test, implement the minimum behavior, then refactor while tests remain green.
8. Record completed tasks and actual verification results. Resolve ambiguity with the user and obtain approval for requirement changes before implementing them.
9. Review acceptance evidence and summarize the changes. Commit only when explicitly requested.

Each feature must identify exclusions, dependencies, requirement-to-test coverage, and any manual checks. A checkbox means verified completion, not intention. Never label unrun checks as passed.

## Current baseline

Inspected for feature 001: Laravel `^13.17`, PHP `^8.3`; Sail targets PHP 8.5 and runs MySQL 8.4 and Redis on the shared `sail` network. The container reports PHP 8.5.11. Vite 8, Laravel Vite plugin 3, and Tailwind 4 are installed. `resources/js/app.js` is an empty entry point; the welcome Blade view uses the existing CSS/JS Vite entries.

Vue, TypeScript, the Vue Vite plugin, Vitest, Vue Test Utils, and a DOM test environment are not yet installed. No TypeScript or Vitest configuration exists. PHPUnit has basic unit and welcome-page feature tests; `phpunit.xml` selects database `testing` and array cache/session drivers. Pest is not installed. These are observed facts, not setup tasks performed by this documentation change.

## Feature index

| Feature | Status | Scope |
| --- | --- | --- |
| [001 — Chatbot widget](001-chatbot-widget/requirements.md) | Draft — awaiting user approval | Floating Vue 3 widget with local mocked responses |
