<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Docker Development Environment

- This project uses Laravel Sail and Docker Compose.
- PHP, Composer, MySQL, Redis, and Node.js run in Docker.
- Do not run local PHP, Composer, or Artisan commands
  when the equivalent Sail command is available.
- Use `./vendor/bin/sail artisan` for Artisan commands.
- Use `./vendor/bin/sail composer` for Composer.
- Use `./vendor/bin/sail npm` for Node/npm.
- Use `./vendor/bin/sail artisan test` for tests.
- Use `./vendor/bin/sail php vendor/bin/pint --test`
  for formatting checks.
- Never run `docker compose down -v` without approval.
- Never delete Docker volumes without approval.
- Do not change published ports without explaining why.
- Do not commit secrets from `.env`.

## Spec-Driven Development

This project follows Spec-Driven Development (SDD).

### Source of Truth
- Feature specifications live in specs/.
- Read relevant specifications before editing code.
- Do not implement features without approved specifications.
- If requirements are ambiguous, ask for clarification.
- Do not silently change requirements.

### Development Workflow
1. Read requirements.md.
2. Review design.md.
3. Review tasks.md.
4. Implement one task at a time.
5. Write tests for acceptance criteria.
6. Implement the minimum code to pass tests.
7. Run tests and formatting checks.
8. Update task status and verification results.
9. Summarize changes for review.

### Technical Standards
- Laravel backend with Docker Sail.
- Vue 3 Composition API and TypeScript frontend.
- Use existing Vite configuration.
- MySQL for persistence.
- Redis for rate limiting and queues.
- PHPUnit/Pest for backend tests.
- Vitest for frontend tests.

### Git Rules
- Never commit or push automatically.
- Do not modify unrelated files.
- Keep changes scoped to the active specification.
- Do not execute destructive database commands.

