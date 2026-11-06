## Issue Reference
<!-- Use a closing keyword so the issue closes automatically on merge, e.g. "Fixes #123". Separate multiple issues with commas. -->

## Description
<!-- Please describe your changes in detail. -->

## How To Test This?
<!-- Please describe in detail how to test the changes made in this pull request, including whether you tested PIM + MDM, MDM only, or both. -->

## Screenshots
<!-- For UI changes, add before/after screenshots or a short screen recording. Remove this section otherwise. -->

## Checklist
- [ ] `vendor/bin/pest packages/Webkul/MasterDataManagement/tests` passes locally
- [ ] `vendor/bin/pint --test` reports no style issues
- [ ] `vendor/bin/phpstan analyse` reports no new errors
- [ ] Tailwind classes are reordered
- [ ] New user-facing strings use translation keys (all 33 locales)
- [ ] New migrations work on MySQL, MariaDB and PostgreSQL
- [ ] Target branch is `main`

## Documentation
- [ ] My pull request requires an update to the README or the documentation repository.
<!-- If checked, please describe in detail what needs to be changed. -->
