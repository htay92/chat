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
