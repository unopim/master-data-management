# UnoPim Master Data Management

Master Data Management (MDM) for [UnoPim](https://github.com/unopim/unopim): governed master records with configurable entity types, matching and merging, change approvals, data quality, code lists, cross-references to source systems, imports and a REST API.

It runs on top of UnoPim, either **alongside PIM** (products, categories and master data in one admin) or **standalone** (MDM only, PIM screens switched off).

## Requirements

- UnoPim 3.x with platform modules (`unopim:install --modules`)
- PHP 8.4
- MySQL 8, MariaDB 10.6+ or PostgreSQL 16

## Installation with composer

```bash
composer require unopim/master-data-management
php artisan masterdata:install
```

Options:

| Option | Effect |
|---|---|
| `--standalone` | Run MDM without PIM: PIM menus and routes are switched off |
| `--demo` | Also install the manufacturing demo data |

On a fresh install you can pick the modules up front instead:

```bash
php artisan unopim:install --modules=pim,mdm   # PIM + MDM
php artisan unopim:install --modules=mdm       # MDM only
```

Modules can be changed later under **Configuration › General › Platform**.

Start a queue worker so matching, quality scans and imports run:

```bash
php artisan queue:work
```

## Installation without composer

1. Unzip the package into `packages/Webkul/MasterDataManagement`.
2. Add to the `autoload.psr-4` section of the root `composer.json`:
   ```json
   "Webkul\\MasterDataManagement\\": "packages/Webkul/MasterDataManagement/src"
   ```
3. Register the provider in `bootstrap/providers.php`:
   ```php
   Webkul\MasterDataManagement\Providers\MasterDataServiceProvider::class,
   ```
4. Run `composer dump-autoload` and `php artisan masterdata:install`.

## Running the tests

1. Add to the `autoload-dev.psr-4` section of the root `composer.json`:
   ```json
   "Webkul\\MasterDataManagement\\Tests\\": "vendor/unopim/master-data-management/tests/"
   ```
2. Add to `tests/Pest.php`:
   ```php
   uses(Webkul\MasterDataManagement\Tests\MasterDataTestCase::class)->in('../vendor/unopim/master-data-management/tests');
   ```
3. Run:
   ```bash
   composer dump-autoload
   vendor/bin/pest vendor/unopim/master-data-management/tests
   ```
