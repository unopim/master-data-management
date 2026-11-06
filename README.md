<p align="center">
  <a href="https://unopim.com/">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://github.com/user-attachments/assets/5001c5b0-1ef3-4887-a907-f9c20082b0e6">
      <source media="(prefers-color-scheme: light)" srcset="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a">
      <img src="https://github.com/user-attachments/assets/a1e6793d-376e-4452-925b-c72b7d07389a" alt="UnoPim logo" width="320">
    </picture>
  </a>
</p>

<h3 align="center">Master Data Management for UnoPim</h3>

<p align="center">
  <a href="https://github.com/unopim/master-data-management/actions/workflows/pest_tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/unopim/master-data-management/pest_tests.yml?branch=main&label=tests" alt="Tests"></a>
  <a href="https://github.com/unopim/master-data-management/actions/workflows/linting_tests.yml"><img src="https://img.shields.io/github/actions/workflow/status/unopim/master-data-management/linting_tests.yml?branch=main&label=lint" alt="Lint"></a>
  <a href="https://github.com/unopim/master-data-management/actions/workflows/static_analysis.yml"><img src="https://img.shields.io/github/actions/workflow/status/unopim/master-data-management/static_analysis.yml?branch=main&label=phpstan" alt="Static Analysis"></a>
  <a href="https://github.com/unopim/master-data-management/blob/main/composer.json"><img src="https://img.shields.io/badge/php-%5E8.4-777BB4?logo=php&logoColor=white" alt="PHP Version"></a>
  <a href="https://github.com/unopim/master-data-management/blob/main/LICENSE"><img src="https://img.shields.io/github/license/unopim/master-data-management" alt="License"></a>
</p>

<p align="center">
  <a href="https://github.com/unopim/master-data-management/stargazers"><img src="https://img.shields.io/github/stars/unopim/master-data-management?style=social" alt="GitHub Stars"></a>
  <a href="https://x.com/uno_pim"><img src="https://img.shields.io/twitter/follow/uno_pim?style=social" alt="Follow on X"></a>
</p>

<p align="center">
  <a href="https://unopim.com/">🌐&nbsp;Website</a>
  &nbsp;·&nbsp;
  <a href="https://devdocs.unopim.com/">📘&nbsp;Documentation</a>
  &nbsp;·&nbsp;
  <a href="#-installation">🚀&nbsp;Installation</a>
  &nbsp;·&nbsp;
  <a href="https://github.com/unopim/unopim">🧩&nbsp;UnoPim</a>
</p>

UnoPim Master Data Management (MDM) is an open-source package that turns [UnoPim](https://github.com/unopim/unopim) into a hub for governed master data: sites, suppliers, customers, equipment, locations or any other business entity you model. It keeps one trusted golden record per real-world thing, links it to its IDs in every source system, finds and merges duplicates, holds sensitive changes for approval, and publishes the result to downstream consumers.

It runs **alongside PIM** — products, categories and master data in one admin — or **standalone**, with the PIM screens switched off.

---

## ✨ Features

- **Configurable entity types**
  Model any master data domain from the admin, without code. Each entity type has its own fields, label field, icon, menu position and record code strategy (a pattern such as `{TYPE}-{seq:5}`, built from identifier fields, or taken from the source ID). Schemas are published as numbered, immutable versions.

- **Rich field types**
  Text, textarea, integer, decimal, boolean, date, date-time, e-mail, URL, select and multiselect backed by code lists, references to other records, files and images — plus measurement fields when the UnoPim Measurement package is loaded. Fields can be required, unique, searchable and carry validation rules. New field types plug in through `config/masterdata.php`.

- **Golden records with a lifecycle**
  Records move through draft, active, retired, merged and erased states. Optimistic locking stops two editors overwriting each other, and every change is kept in the UnoPim history.

- **Hierarchies and relations**
  Hierarchical entity types form trees (site → department → line → equipment) with allowed parent types, stored as materialised paths so subtree reads stay fast at volume. Relation types link records across entity types with one-to-one or one-to-many cardinality.

- **Source systems and cross-references (crosswalk)**
  Register the ERP, MES, CMMS, CRM and other systems that feed you, with a trust priority each. Every record keeps its external IDs per source system, and the crosswalk view answers "which golden record is ID `X` in system `Y`?" from the admin or the API.

- **Matching and the match inbox**
  Incoming source rows that no cross-reference resolves land in a match inbox with suggested existing records. Stewards accept a match, create a new record or reject the row, and two stewards can never decide the same row twice. Per entity type you can match on every write and auto-accept a single suggestion above a confidence threshold.

- **Match rules**
  Score candidates field by field with exact, fuzzy, token, phonetic, numeric and date comparators, each with its own weight, and mark rules as blocking to narrow the candidate set. Comparators are pluggable.

- **Merge, unmerge and survivorship**
  Fold a duplicate into its surviving golden record — values, cross-references and children move with it — and undo the merge later. Survivorship rules decide each field's winning value: source priority, most recent, most complete, keep target or manual choice.

- **Code lists (reference data)**
  Maintain versioned, translatable code lists — countries, units, statuses, classifications — with draft and published versions. Aliases map the values source systems send (`DE`, `Germany`, `Deutschland`) onto your canonical codes, globally, per code list or per field.

- **Approvals (maker-checker)**
  Mark an entity type as requiring approval and its creates, updates, retirements, merges and cross-reference changes are held as change requests with a field-level diff until an approver decides them. Requesters cannot approve their own changes, approvers are notified in UnoPim, and automated changes such as auto-accepted matches wait too. The change gate is a contract, so a custom workflow can replace it.

- **Data access grants**
  Scope who may view, edit or approve records of an entity type, down to a subtree of a hierarchy, by role or by user, on top of the UnoPim ACL. Every admin screen and API call applies the same scope.

- **Data quality**
  Each record gets a completeness score. Quality scans flag invalid values, broken hierarchies, missing required cross-references and likely duplicates, and stewards resolve or ignore each issue from a quality dashboard.

- **Lineage**
  See where a record came from and how it got here: its IDs per source system with when each was last seen, who created and changed it and which fields each change touched, the schema version it was saved under, its place in the hierarchy, and which export jobs publish it.

- **Right to erasure**
  Erase the personal data of a retired record while keeping a tombstone, so its UUID, source IDs and relations still resolve and the next sync cannot recreate it.

- **Import and export**
  Import records, cross-references and code lists from CSV or XLSX through the UnoPim data transfer pipeline, with column mapping and sample files. Export records as JSONL or CSV — optionally only those changed since the last run — to any Laravel filesystem disk, such as local storage, S3 or Azure Blob Storage, for data lakes and analytics tools. Integration tools can drop files into an inbox folder that `masterdata:import` picks up on a schedule.

- **REST API**
  Read and write records, entity types, code lists and the match inbox under `/api/v1/rest/masterdata`, with OAuth2 (Passport) authentication, API ACL permissions, cursor pagination, field filters, ETags, `Idempotency-Key` replay for safe retries, and upsert by source system ID.

- **Icon library**
  Pick entity type icons from a bundled set of [Lucide](https://lucide.dev/) icons or upload your own SVGs.

- **Events for extensions**
  Record created, updated, retired, merged, unmerged and erased, cross-references changed, schema and code list published, change request submitted and decided — all dispatched as Laravel events. Models, repositories and contracts follow the UnoPim Concord conventions, so the package can be extended without forking it.

- **Manufacturing demo**
  One command seeds a complete manufacturing sample: a site → department → line → equipment hierarchy, five source systems, a crosswalk, code lists, aliases and stewardship users with their roles.

- **Localization and themes**
  The admin screens ship in all 33 UnoPim locales and follow the UnoPim light and dark themes.

## 📋 Requirements

- A UnoPim 3.x installation with platform modules (the `unopim:install --modules` option).
- PHP 8.4.1+ and Composer 2.6+.
- MySQL 8.0.32+, MariaDB 10.11 LTS or 11.8 LTS, or PostgreSQL 16 — the same database UnoPim runs on.
- A queue worker. Matching, quality scans, hierarchy moves and imports run on the queue.

Elasticsearch is not required: master data search and filtering run on the database.

## 🚀 Installation

### With Composer

From the root of your UnoPim installation:

```bash
composer require unopim/master-data-management
php artisan masterdata:install
```

`masterdata:install` runs the migrations and switches the MDM module on. Its options:

| Option | Effect |
|---|---|
| *(none)* | **PIM + MDM** — MDM is added to the modules already enabled. |
| `--standalone` | **MDM only** — PIM menus and routes are switched off. |
| `--demo` | Also installs the manufacturing demo data. |

```bash
php artisan masterdata:install                      # PIM + MDM
php artisan masterdata:install --standalone         # MDM only
php artisan masterdata:install --standalone --demo  # MDM only, with demo data
```

On a fresh UnoPim install you can choose the modules up front instead:

```bash
php artisan unopim:install --modules=pim,mdm   # PIM + MDM
php artisan unopim:install --modules=mdm       # MDM only
```

Modules can be changed later under **Configuration › General › Platform**.

Start a queue worker so matching, quality scans and imports run:

```bash
php artisan queue:work
```

To offer the import sample files for download, publish them once:

```bash
php artisan vendor:publish --tag=masterdata-samples
```

### Without Composer

1. Copy the package into `packages/Webkul/MasterDataManagement`.
2. Add it to the `autoload.psr-4` section of the root `composer.json`:
   ```json
   "Webkul\\MasterDataManagement\\": "packages/Webkul/MasterDataManagement/src"
   ```
3. Register the provider in `bootstrap/providers.php`:
   ```php
   Webkul\MasterDataManagement\Providers\MasterDataServiceProvider::class,
   ```
4. Run `composer dump-autoload` and `php artisan masterdata:install`.

### Demo data

The demo can also be installed, or reinstalled, on its own:

```bash
php artisan masterdata-demo:install --password='<a password you choose>'
```

It creates stewardship users who sign in with the given password. Without `--password` they get the default `admin123`, so set your own on any server reachable by others. Use `--force` to run it again over an existing demo.

## 🔼 Upgrading

```bash
composer update unopim/master-data-management
php artisan migrate
php artisan optimize:clear
```

Restart your queue workers afterwards so they load the new code. Read the [CHANGELOG](CHANGELOG.md) before upgrading.

## ⚙️ Configuration

The defaults work out of the box. These settings are read from `.env`:

| Variable | Default | Purpose |
|---|---|---|
| `MASTERDATA_EXPORT_DISK` | `local` | Filesystem disk the record exporter writes to, e.g. `s3` or `azure`. |
| `MASTERDATA_EXPORT_PATH` | `masterdata/exports` | Folder on that disk; files land under `{path}/{entity_type}/{Y/m/d}/`. |
| `MASTERDATA_IMPORT_DISK` | `local` | Disk that scheduled imports read their inbox from. |
| `MASTERDATA_IMPORT_PATH` | `masterdata/imports` | Inbox folder; files go into `{path}/{job_code}/` and move to `processed/`. |
| `MASTERDATA_IMPORT_USER` | — | E-mail of the admin scheduled imports run as. |

Matching comparators, survivorship strategies, quality rules, field types and the approval change gate are registered in the package's `src/Config/masterdata.php` and can be overridden or extended from your application's config.

Useful commands:

| Command | Purpose |
|---|---|
| `masterdata:import {job_code}` | Import the oldest file in a job's inbox (`--file`, `--user`, `--sync`). |
| `masterdata:quality:scan {entity_type?}` | Run a data quality scan (`--now` to run without the queue). |
| `masterdata:idempotency:prune` | Remove expired API idempotency keys; scheduled daily. |

Add the Laravel scheduler to cron (`php artisan schedule:run` every minute) so pruning and scheduled imports run.

## 🔌 REST API

The API uses the same OAuth2 clients as the [UnoPim REST API](https://devdocs.unopim.com/). Grant the integration's API key the **Master data** permissions it needs, then call the endpoints under `/api/v1/rest/masterdata`:

| Method | Endpoint | Purpose |
|---|---|---|
| `GET` | `/entity-types`, `/entity-types/{code}` | Entity types and their schema |
| `GET` | `/code-lists`, `/code-lists/{code}` | Code lists and their codes |
| `GET` | `/{type}/records` | List records (`limit`, `cursor`, `filter[...]`, `filter[updated_since]`) |
| `GET` | `/{type}/records/{uuid}` | Read one record |
| `POST` | `/{type}/records` | Create a record |
| `PUT` | `/{type}/records/{uuid}` | Update a record |
| `DELETE` | `/{type}/records/{uuid}` | Retire a record |
| `PUT` | `/{type}/records/by-xref/{system}/{externalId}` | Create or update a record by its source system ID |
| `GET` | `/xref/resolve?system=&type=&id=` | Resolve a source system ID to its golden record |
| `POST` | `/match-candidates` | Submit a source row to the match inbox |

Updates and deletes take the record revision in an `If-Match` header (responses carry it as the `ETag`), so a stale write answers `412` instead of overwriting newer data. Send an `Idempotency-Key` header on writes so a retried request is never applied twice. A write to an entity type that requires approval answers `202` with the pending change request instead of the record.

## 🧪 Running the tests

The tests run inside a UnoPim installation.

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

Continuous integration runs the suite on MySQL, MariaDB and PostgreSQL, with and without Elasticsearch, along with Pint, Larastan, the translation audit and Playwright end-to-end tests.

## 🤝 Contributing

Found a bug or want to add a feature? Open an issue or submit a pull request — see [CONTRIBUTING.md](.github/CONTRIBUTING.md) for the full flow, and follow our [Code of Conduct](CODE_OF_CONDUCT.md).

## 👥 Community and support

<p align="center">
  <a href="https://unopim.com/"><img src="https://img.shields.io/badge/Website-unopim.com-2563eb?style=for-the-badge&logo=google-chrome&logoColor=white" alt="Website"></a>
  <a href="https://devdocs.unopim.com/"><img src="https://img.shields.io/badge/Docs-devdocs-059669?style=for-the-badge&logo=gitbook&logoColor=white" alt="Documentation"></a>
  <a href="https://www.youtube.com/@UnoPIM"><img src="https://img.shields.io/badge/YouTube-@UnoPIM-FF0000?style=for-the-badge&logo=youtube&logoColor=white" alt="YouTube"></a>
  <a href="https://x.com/uno_pim"><img src="https://img.shields.io/badge/X-uno__pim-000000?style=for-the-badge&logo=x&logoColor=white" alt="X / Twitter"></a>
  <a href="https://www.facebook.com/unopim/"><img src="https://img.shields.io/badge/Facebook-unopim-1877F2?style=for-the-badge&logo=facebook&logoColor=white" alt="Facebook"></a>
</p>

Report bugs and request features in [GitHub Issues](https://github.com/unopim/master-data-management/issues). For help from our team, [create a support ticket](https://webkul.uvdesk.com/en/customer/create-ticket/).

## 🔒 Security

If you discover a security vulnerability, please follow our [Security Policy](SECURITY.md) and report it to [support@webkul.com](mailto:support@webkul.com). Do not open a public issue.

## 📝 License

UnoPim Master Data Management is open-source software distributed under the [MIT License](LICENSE). The bundled Lucide icons are distributed under the [ISC License](src/Resources/icons/LICENSE).
