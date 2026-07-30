# POS CodeIgniter 4 migration

This application runs on CodeIgniter 4.7.4 and PHP 8.5 while preserving the
existing POS views, theme, uploads, routes, controllers, and models.

## Local start

Run `start-pos.cmd` from the project directory, then open
<http://127.0.0.1:8081>.

The launcher uses the project-local `php-ci4.ini` when present. Copy
`php-ci4.ini.example` to `php-ci4.ini` if a local PHP extension configuration
is required.

## Database

The application expects the database `pos` by default. Connection values live
in `.env`.

Do not commit or share `.env`, database dumps, or uploaded business data.

## Architecture

The HTTP entry point, router, request/response lifecycle, configuration,
sessions, CSRF protection, database connection, and error handling are
CodeIgniter 4 services.

The original controllers, models, helpers, and views are retained under
`legacy/application`. `app/Legacy/Bootstrap.php` adapts the CodeIgniter 3 APIs
used by that business code to CodeIgniter 4 APIs. This keeps the current UI and
business workflows intact while allowing individual modules to be converted
to native namespaced CodeIgniter 4 code over time.

## Validation commands

```bat
php -c php-ci4.ini spark routes
php -c php-ci4.ini spark phpini:check
php -c php-ci4.ini vendor\bin\phpunit
```
