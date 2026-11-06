# Unreleased

First release of UnoPim Master Data Management as a standalone package, `unopim/master-data-management`.

## New features

- Configurable entity types with versioned schemas, 14 field types (plus measurement fields when the Measurement package is loaded), record code strategies, hierarchies and relation types.
- Golden records with a draft, active, retired, merged and erased lifecycle, optimistic locking and change history.
- Source systems and a crosswalk of cross-references from each golden record to its IDs in every source system.
- A match inbox with suggested records, weighted match rules with exact, fuzzy, token, phonetic, numeric and date comparators, match on write and an optional auto-accept threshold.
- Merge and unmerge, with survivorship rules by source priority, most recent, most complete, keep target or manual choice.
- Versioned, translatable code lists with aliases that map source values onto canonical codes.
- Maker-checker approvals for entity types that require them, with field-level diffs and approver notifications.
- Data access grants that scope view, edit and approve rights to an entity type or a hierarchy subtree, per role or per user.
- Completeness scores, quality scans and a quality dashboard.
- Record lineage and erasure of personal data with a tombstone.
- Record, cross-reference and code list imports, scheduled inbox imports with `masterdata:import`, and JSONL or CSV record exports to any filesystem disk.
- REST API under `/api/v1/rest/masterdata` with cursor pagination, ETags, `If-Match` revisions, `Idempotency-Key` replay and upsert by source system ID.
- An icon library with bundled Lucide icons and SVG uploads.
- `masterdata:install` command with `--standalone` and `--demo` options, and a manufacturing demo dataset.
