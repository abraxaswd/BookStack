# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

BookStack is a Laravel-based documentation platform. This is a fork at `abraxaswd/BookStack` with custom OIDC enhancements (email-missing handling, existing user detection). The upstream is `BookStackApp/BookStack`.

## Development Branch

All development happens on the `development` branch. PRs target `development`, not `release`.

## Common Commands

### Build Frontend Assets
```bash
npm install
npm run build          # Development build
npm run production     # Minified production build
npm run dev            # Build with sourcemaps + watch
```

### PHP Testing
```bash
composer test                                        # All tests
php vendor/bin/phpunit tests/Auth/OidcTest.php       # Single file
php vendor/bin/phpunit --filter test_method_name     # Single test method
php vendor/bin/phpunit tests/User                    # Directory
composer refresh-test-database                       # Setup/reset test DB
```

Test DB expects: `bookstack-test:bookstack-test@127.0.0.1/bookstack-test` (or set `TEST_DATABASE_URL` in `.env`).

### Linting & Static Analysis
```bash
composer lint            # PHP_CodeSniffer (PSR-12)
composer format          # Auto-fix PHP formatting
composer check-static    # PHPStan + Larastan (level 3)
npm run lint             # ESLint
npm run fix              # ESLint auto-fix
```

### Docker Development
```bash
docker-compose up                                    # Start dev environment (port 8080)
docker-compose run app php artisan migrate --database=mysql_testing
docker-compose run app php artisan db:seed --class=DummyContentSeeder --database=mysql_testing
docker-compose run app php vendor/bin/phpunit        # Run tests in Docker
```

### Production Docker Image
```bash
docker build -f dev/docker/Dockerfile.prod -t ghcr.io/abraxaswd/bookstack:latest .
docker push ghcr.io/abraxaswd/bookstack:latest
```

## Architecture

Standard Laravel MVC (PHP 8.2+, Laravel 12). Key directories under `app/`:

- **Access/** — Authentication (OIDC, SAML, LDAP, Social OAuth2), MFA, login/registration services
- **Entities/** — Core content models (Book, Chapter, Page, Bookshelf) with Controllers, Repos, Tools
- **Permissions/** — Access control system
- **Api/** — REST API endpoints (prefix `/api/`)
- **Theming/** — Visual and logical theme override system

Frontend: Server-side Blade templates with thin JS layer (esbuild). Source in `resources/js/` and `resources/sass/`, built to `public/dist/`.

## Authentication Flow (OIDC)

Custom enhancement in `app/Access/Oidc/OidcService.php`:
- When OIDC provider returns no email: checks if user exists by `external_auth_id` → uses stored email for returning users
- For new users without email: stores pending details in session, redirects to `/oidc/email` prompt
- Session data preserved on login failure so users can retry without restarting the OIDC flow
- Debug: set `OIDC_DUMP_USER_DETAILS=true` in `.env` to dump ID token claims in the browser

## Testing Conventions

- Tests in `tests/`, extend `Tests\TestCase`, methods are `public function test_snake_case()`
- Tests are mostly functional (simulate user actions with DB), not unit tests
- Helpers: `$this->asAdmin()`, `$this->asEditor()`, `$this->asViewer()`, `$this->entities`, `$this->users`, `$this->permissions`
- Hard-code expected text/URLs in assertions rather than using dynamic references
- All external HTTP/LDAP calls must be mocked

## GitHub Account

All GitHub operations (PRs, issues, comments, releases) must use the **AbraxasWD** account. Before running `gh` commands, verify the correct account is active (`gh auth status`). This ensures all actions are traceable and run under the correct responsibility.

## Commit Convention

`TYPE: description` — e.g. `FEAT:`, `FIX:`, etc.

## Security
 - Review all changes in context of security issues. For example XSS, SQL-Injections, auth bypass. 
 - Remember we are working on a very high security context.

## Translations

Managed via Crowdin, not GitHub PRs. Files in `lang/`.
