# How to contribute to UnoPim Master Data Management

### **Security Vulnerabilities**

Please do **not** report security vulnerabilities through public GitHub issues. Refer to our [Security Policy](../SECURITY.md) for responsible disclosure instructions.

### **Bug Reporting**

1. Verify that the bug was not already reported by searching on GitHub in the [Issues section](https://github.com/unopim/master-data-management/issues).
If you're unable to find an open issue, [open a new one](https://github.com/unopim/master-data-management/issues/new?template=bug.yml).

2. Verify that the bug you are reporting is a general issue and not specific to your individual setup.

3. If the bug is in UnoPim itself rather than in master data management — products, categories, users, data transfer — report it on the [UnoPim repository](https://github.com/unopim/unopim/issues) instead.

#### **Did you fix a bug?**

1. To provide a code contribution for an issue you will need to set up your own fork of the [repository](https://github.com/unopim/master-data-management).
Make your code changes, commit the changes and make a [Pull Request](https://help.github.com/articles/about-pull-requests/) to the [repository](https://github.com/unopim/master-data-management).
2. Target the `main` branch with your pull request.
3. Separate each fix into a new branch in your repository and name it with the issue ID e.g. `issue-1234`.
4. Use the [Conventional Commits](https://www.conventionalcommits.org/) format for your commit messages and reference the issue, e.g.
```fix(masterdata): keep the survivor's code when merging records (#1234)```
5. Please follow the pull request [template](https://github.com/unopim/master-data-management/blob/main/.github/PULL_REQUEST_TEMPLATE.md) as much as possible.

### **Did you create a new feature or enhancement?**

1. To provide a code contribution for a new feature or enhancement a [feature request](https://github.com/unopim/master-data-management/issues/new?template=feature_request.yml) report should be created in case it doesn't exist.
2. To contribute a feature, you must create a forked repository and set up your git and development environment.
3. Make sure your commit messages are relevant and descriptive.
4. Please follow the pull request [template](https://github.com/unopim/master-data-management/blob/main/.github/PULL_REQUEST_TEMPLATE.md) as much as possible.

### **Development setup**

The package is developed inside a UnoPim installation:

1. Install [UnoPim](https://github.com/unopim/unopim) with platform modules.
2. Clone your fork into `packages/Webkul/MasterDataManagement`.
3. Register it as a Composer path repository and require it:
   ```bash
   composer config repositories.mdm '{"type":"path","url":"packages/Webkul/MasterDataManagement","options":{"symlink":true}}' --json
   composer require unopim/master-data-management:"*@dev"
   php artisan masterdata:install
   ```
4. Add `"Webkul\\MasterDataManagement\\Tests\\": "packages/Webkul/MasterDataManagement/tests/"` to the root `autoload-dev.psr-4`, register `Webkul\MasterDataManagement\Tests\MasterDataTestCase` for `../packages/Webkul/MasterDataManagement/tests` in `tests/Pest.php`, and run `composer dump-autoload`.

### **Tests**

Every pull request that adds functionality or changes behaviour **must** include automated tests that cover it. Pull requests without tests for new code are not merged.

- **New features** — add [Pest](https://pestphp.com/) feature or unit tests under `tests/Feature/` or `tests/Unit/`.
- **Bug fixes** — add a test that fails without the fix and passes with it, so the bug cannot return.
- **Admin UI flows** — add or update a [Playwright](https://playwright.dev/) spec when the change affects a user-visible flow.

### **Coding standards**

- PHP code follows the [Laravel Pint](https://laravel.com/docs/pint) `laravel` preset (PSR-12), configured in `pint.json`.
- Static analysis runs [Larastan](https://github.com/larastan/larastan) at the UnoPim level; new code must not add errors.
- User-facing text uses translation keys, added to all 33 locales under `src/Resources/lang/`.
- Database code must work on MySQL, MariaDB and PostgreSQL, and with a table prefix (`DB_PREFIX`) set.
- Data access goes through the package's repositories and Concord proxy models, so other packages can extend it.

### **Before submitting a pull request**

Continuous integration runs these checks on every pull request — run them locally from the UnoPim root first:

```bash
vendor/bin/pest packages/Webkul/MasterDataManagement/tests   # PHP tests must pass
vendor/bin/pint --test packages/Webkul/MasterDataManagement  # code style must pass (run without --test to auto-fix)
vendor/bin/phpstan analyse packages/Webkul/MasterDataManagement/src --memory-limit=1G
php artisan unopim:translations:check                        # every locale must have every key
```

CI also runs the Pest suite on MySQL, MariaDB and PostgreSQL, with and without Elasticsearch, and the Playwright end-to-end tests.
