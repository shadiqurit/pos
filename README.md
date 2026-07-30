# POS on CodeIgniter 4

This project runs the existing POS design and business modules on CodeIgniter
4.7 and PHP 8.2 or newer. The migrated application keeps the original
controllers, models, helpers, views, theme, and uploaded design assets under
the `legacy` and `public` directories.

## Requirements

- PHP 8.2 or newer
- Composer 2
- MySQL or MariaDB
- PHP extensions: `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `mysqli`,
  `openssl`, `pdo_mysql`, and `zip`

## Installation

```bat
composer install
copy .env.example .env
```

Edit `.env` with the local database credentials. The default database name in
the example is `pos`. Database dumps and the real `.env` are intentionally
excluded from Git.

On Windows, copy `php-ci4.ini.example` to `php-ci4.ini` if the installed PHP
does not already enable the required extensions.

Start the application:

```bat
start-pos.cmd
```

Then open <http://127.0.0.1:8081>.

## Shared hosting

See [DEPLOY_SHARED_HOSTING.md](DEPLOY_SHARED_HOSTING.md). The recommended
configuration points the domain document root to `public/`. A protected root
`.htaccess` fallback is included for hosts that require the complete project
inside `public_html`.

## Tests

```bat
composer test
```

## Project layout

- `app/` — CodeIgniter 4 application, routing, commands, and compatibility code
- `legacy/application/` — migrated POS business code and views
- `public/theme/` — original interface assets
- `public/uploads/` — images required by the existing interface/data
- `writable/` — generated cache, logs, sessions, and temporary files

Do not commit `.env`, database dumps, `vendor`, or generated `writable` files.
