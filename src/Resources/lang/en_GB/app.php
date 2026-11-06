<?php

return [
    'menu' => [
        'master-data' => 'Master Data',
        'icons'       => 'Icons',
    ],

    'acl' => [
        'master-data' => 'Master Data',
        'icons'       => [
            'index'  => 'Icons',
            'create' => 'Create',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ],

        'records' => [
            'erase' => 'Erase Permanently',
        ],
    ],

    'dashboard' => [
        'title'      => 'Master Data',
        'inside'     => 'Inside: :types',
        'top-level'  => 'Top level',
        'standalone' => 'Standalone',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Master Data',
            'records'     => 'Records',
            'upsert'      => 'Upsert by Source System ID',
        ],

        'validation-failed'     => 'Validation failed.',
        'change-held'           => 'The change is waiting for approval.',
        'xref-not-found'        => 'No record matches this external id.',
        'entity-type-not-found' => 'The entity type was not found.',
        'record-not-found'      => 'The record was not found.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Entity Type',
            'source-system'       => 'Source System',
            'code-list'           => 'Code List',
            'entity-type-info'    => 'The entity type whose records the file holds.',
            'source-system-info'  => 'The system the file comes from. Its external IDs are read from the external_id column.',
            'code-list-info'      => 'The list to import into. Codes are written to its draft version, which you publish separately.',
            'mapping-info'        => 'One line per renamed column: file column=field code, or field code-locale for a per-locale field, e.g. Asset Num=external_id and Description=name. Columns without a line are read by their own name.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Choose an active entity type whose records you may create or edit.',
            'source-system-invalid'              => 'Choose an active source system.',
            'code-list-invalid'                  => 'Choose an existing code list you may edit.',
            'external-id-without-source-system'  => 'The file has an external ID column (:column). Choose the source system it comes from, or remove the column.',
        ],

        'schedule' => [
            'job-not-found'  => 'No import job has the code :code.',
            'not-masterdata' => 'The job :code is not a master data import.',
            'no-user'        => 'Name the admin the import runs as with --user or MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'No active admin has the e-mail :email.',
            'file-not-found' => 'The file :path was not found on the :disk disk.',
            'nothing'        => 'No file is waiting in :path on the :disk disk.',
            'queued'         => 'Queued :file for the job :code, tracked as #:id.',
            'imported'       => 'Imported :file for the job :code, tracked as #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Manufacturing demo ready: :types entity types, :systems source systems, :records records in a site tree, :xrefs cross-references, :lists code lists, :fields fields, :aliases aliases.',
        'invalid-template' => 'The template :name could not be read or has an unsupported format version.',
    ],

    'platform-module' => [
        'name' => 'Master Data (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Entity types deleted: :count.',
        'mass-delete-skipped' => 'Not deleted because they still have records: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Parent Types',
            'records-count'        => 'Records',
            'fields-count'         => 'Fields',
            'updated-at'           => 'Updated',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Sort Order',
        ],

        'edit' => [
            'schema-version' => 'Schema v:version',
        ],

        'form' => [
            'basics'                    => 'Basics',
            'identity'                  => 'Identity',
            'hierarchy'                 => 'Hierarchy',
            'governance'                => 'Governance',
            'quality-matching'          => 'Quality & Matching',
            'matching-info'             => 'Incoming source rows are matched to existing records in the match inbox.',
            'auto-match-threshold'      => 'Auto Match Threshold',
            'auto-match-off'            => 'Off',
            'auto-match-threshold-info' => 'Leave empty to review every row. From 0.5 to 1: a row whose best suggestion scores at least this, with no other suggestion as high, is accepted automatically. Types that require approval still hold the link for approval.',
            'match-on-write'            => 'Check for Duplicates on Save',
            'match-on-write-info'       => 'Each saved record is checked against the other records in the background, using the match rules. Turn off to find duplicates only in quality scans.',
            'manage-icons'              => 'Manage icons',
            'sort-order'                => 'Sort Order',
            'sort-order-info'           => 'Lower numbers are listed first, from 0 to 9999; types with the same number are listed by code.',
        ],

        'quick-create' => [
            'code-info'             => 'Lowercase letters, digits and underscores, starting with a letter. It cannot be changed after saving.',
            'code-pattern-info'     => 'Use {seq} or {seq:6} for the running number and {TYPE} for the type code, for example {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Records arrive from several source systems and are matched and merged into one golden record.',
                'centralized'   => 'Records are created and maintained here, then published to the systems that use them.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Match rule on :target',
        ],

        'show' => [
            'auto-matched' => 'Auto-matched',
            'auto-pending' => 'Matched automatically; the link waits for approval.',
        ],

        'datagrid' => [
            'decision' => 'Decision',
            'auto'     => 'Auto-matched',
            'manual'   => 'Manual',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Consumed By',
            'empty'        => 'No export job publishes records of this type and status.',
            'job'          => 'Export Job',
            'format'       => 'Format',
            'mode'         => 'Mode',
            'full'         => 'Full',
            'delta'        => 'Changes only',
            'last-run-at'  => 'Last Completed Run',
            'no-run'       => 'Never run',
            'record-state' => 'This Record',
            'states'       => [
                'never_run'  => 'Not exported yet',
                'pending'    => 'Changed since last run',
                'included'   => 'Included in last run',
                'up_to_date' => 'Up to date',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Icon created successfully.',
        'update-success' => 'Icon updated successfully.',
        'delete-success' => 'Icon deleted successfully.',
        'delete-in-use'  => '{1} This icon is shown by one entity type and cannot be deleted.|[2,*] This icon is shown by :count entity types and cannot be deleted.',
        'index'          => [
            'title'      => 'Icons',
            'create-btn' => 'Add Icon',
        ],

        'create' => [
            'title'    => 'Add Icon',
            'save-btn' => 'Save Icon',
        ],

        'edit' => [
            'title'    => 'Edit Icon',
            'save-btn' => 'Save Icon',
        ],

        'form' => [
            'code'      => 'Code',
            'name'      => 'Name',
            'file'      => 'Icon File',
            'file-info' => 'SVG, PNG or WebP, up to :size KB. Use one shape on a transparent background: it takes the theme colour.',
            'preview'   => 'Preview',
            'usage'     => '{0} No entity type shows this icon yet.|{1} Shown by one entity type.|[2,*] Shown by :count entity types.',
        ],

        'library' => [
            'barcode'    => 'Barcode',
            'boxes'      => 'Materials',
            'briefcase'  => 'Vendor',
            'building_2' => 'Company',
            'cpu'        => 'Asset',
            'factory'    => 'Plant',
            'file_text'  => 'Contract',
            'globe'      => 'Country',
            'handshake'  => 'Partner',
            'landmark'   => 'Legal Entity',
            'map_pin'    => 'Location',
            'network'    => 'Hierarchy',
            'package'    => 'Product',
            'store'      => 'Store',
            'tag'        => 'Category',
            'truck'      => 'Supplier',
            'user_round' => 'Contact',
            'users'      => 'Customer',
            'warehouse'  => 'Warehouse',
            'wrench'     => 'Equipment',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Icon',
            'code'    => 'Code',
            'name'    => 'Name',
            'usage'   => 'Used By',
            'edit'    => 'Edit',
            'delete'  => 'Delete',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Matching',
        'title'          => 'Match Rules',
        'info'           => 'Decide how records of this type are compared to find duplicates and to match incoming rows. Without rules, the code and the label are compared.',
        'create-btn'     => 'Add Match Rule',
        'create-success' => 'Match rule created successfully.',
        'update-success' => 'Match rule updated successfully.',
        'delete-success' => 'Match rule deleted successfully.',

        'create' => [
            'title' => 'Add Match Rule',
        ],

        'edit' => [
            'title' => 'Edit Match Rule',
        ],

        'form' => [
            'target'            => 'Compare',
            'select-target'     => 'Select what to compare',
            'target-info'       => 'The record code, its label or a field of this entity type.',
            'comparator'        => 'Comparator',
            'select-comparator' => 'Select a comparator',
            'comparator-info'   => 'How the two values are compared, e.g. Fuzzy for names with typos or Numeric for amounts.',
            'weight'            => 'Weight',
            'weight-info'       => 'How much this rule counts, from 0.1 to 100. The score is the weighted average of the rules the incoming row has a value for.',
            'tolerance'         => 'Tolerance',
            'tolerance-info'    => 'The largest difference between two numbers that still counts as similar. Leave empty to match equal numbers only.',
            'days'              => 'Date Window (days)',
            'days-info'         => 'The most days two dates may be apart and still count as similar. Leave empty to match the same day only.',
            'sort-order'        => 'Sort Order',
            'is-blocking'       => 'Blocking Key',
            'is-blocking-info'  => 'Also look up the records that share this exact value, even when their code and label look different. Fields must be searchable.',
            'save-btn'          => 'Save Match Rule',
        ],

        'targets' => [
            'code'  => 'Code',
            'label' => 'Label',
        ],

        'comparators' => [
            'exact'    => 'Exact',
            'fuzzy'    => 'Fuzzy',
            'token'    => 'Same words',
            'phonetic' => 'Sounds alike',
            'numeric'  => 'Numeric',
            'date'     => 'Date',
        ],

        'datagrid' => [
            'target'     => 'Compare',
            'comparator' => 'Comparator',
            'weight'     => 'Weight',
            'blocking'   => 'Blocking Key',
            'sort-order' => 'Sort Order',
            'yes'        => 'Yes',
            'no'         => 'No',
            'edit'       => 'Edit',
            'delete'     => 'Delete',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Only the code, the label or a searchable field can be a blocking key.',
            'too-many'                => 'An entity type can have at most :count match rules.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Survivorship',
        'title'          => 'Survivorship Rules',
        'info'           => 'Decide which value the golden record keeps when two records of this type are merged. Without rules, the survivor keeps its value unless it is empty.',
        'create-btn'     => 'Add Survivorship Rule',
        'create-success' => 'Survivorship rule created successfully.',
        'update-success' => 'Survivorship rule updated successfully.',
        'delete-success' => 'Survivorship rule deleted successfully.',

        'create' => [
            'title' => 'Add Survivorship Rule',
        ],

        'edit' => [
            'title' => 'Edit Survivorship Rule',
        ],

        'form' => [
            'target'            => 'Applies To',
            'select-target'     => 'Select what the rule applies to',
            'target-info'       => 'A field, the record label, or every field without a rule of its own.',
            'strategy'          => 'Strategy',
            'select-strategy'   => 'Select a strategy',
            'strategy-info'     => 'How the surviving value is picked. Stewards can still change each pick on the merge screen.',
            'source-order'      => 'Source Order',
            'select-sources'    => 'Select source systems',
            'source-order-info' => 'The most trusted source system first. Systems left out follow their priority. Leave empty to use the source system priority alone.',
            'sort-order'        => 'Sort Order',
            'save-btn'          => 'Save Survivorship Rule',
        ],

        'targets' => [
            'default' => 'Default for all fields',
            'label'   => 'Label',
        ],

        'strategies' => [
            'source_priority' => 'Most trusted source',
            'most_recent'     => 'Most recently updated',
            'most_complete'   => 'Most complete',
            'keep_target'     => 'Keep the survivor',
            'manual'          => 'Steward decides',
        ],

        'datagrid' => [
            'target'     => 'Applies To',
            'strategy'   => 'Strategy',
            'sort-order' => 'Sort Order',
            'edit'       => 'Edit',
            'delete'     => 'Delete',
        ],

        'validation' => [
            'target-taken' => 'This entity type already has a survivorship rule for this target.',
            'too-many'     => 'An entity type can have at most :count survivorship rules.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Browse :type below',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Top Level',
        ],

        'datagrid' => [
            'erase'         => 'Erase Permanently',
            'parent'        => 'Parent',
            'top-level'     => 'Top level',
            'children'      => 'Children',
            'view-children' => 'Show the :count records below this one',
        ],

        'erase' => [
            'btn'             => 'Erase Permanently',
            'confirm-title'   => 'Erase this record permanently?',
            'confirm-message' => 'All values, the label and the code of this record and of the duplicates merged into it are removed, along with their history, change requests and quality issues. The record stays as an erased placeholder so its source IDs and links keep resolving. This cannot be undone.',
            'success'         => 'Record erased permanently.',
            'not-retired'     => 'Only a retired record can be erased. Retire it first.',
        ],

        'mass-retire' => [
            'success' => ':count record(s) retired.',
            'pending' => ':count retirement(s) submitted for approval.',
            'skipped' => ':count record(s) skipped because they are not draft or active, or were changed by someone else.',
            'none'    => 'None of the selected records could be retired.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Approval needed: change request #:id',
            'description'           => ':requester submitted a ":operation" change to :type for your approval.',
            'description-automated' => 'An automatic ":operation" change to :type is waiting for your approval.',
        ],
    ],

    'access' => [
        'menu'           => 'Data Access',
        'acl'            => [
            'index'  => 'Data Access',
            'create' => 'Create',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ],
        'principals' => [
            'role' => 'Role',
            'user' => 'User',
        ],
        'actions' => [
            'view'    => 'View',
            'edit'    => 'Edit',
            'approve' => 'Approve',
        ],
        'index' => [
            'title'      => 'Data Access',
            'create-btn' => 'Add Grant',
            'info'       => 'Grants decide which records of scoped entity types each role or user reaches: the chosen record and everything below it. Access from a user\'s role and from grants made to the user add up.',
        ],
        'create' => [
            'title' => 'Add Data Access Grant',
        ],
        'edit' => [
            'title' => 'Edit Data Access Grant',
        ],
        'form' => [
            'save-btn'         => 'Save Grant',
            'principal'        => 'Granted To',
            'principal-info'   => 'A role grant reaches every user with that role. Grant a single user for an exception.',
            'principal-type'   => 'Grant To',
            'role'             => 'Role',
            'user'             => 'User',
            'scope'            => 'Scope',
            'scope-info'       => 'The grant covers the chosen record and every record below it in the hierarchy.',
            'root'             => 'Subtree Root',
            'root-info'        => 'For example a site or a plant. Records below it inherit the grant.',
            'entity-type'      => 'Entity Type',
            'entity-type-info' => 'Limit the grant to one scoped entity type, or leave it empty for every scoped type.',
            'any-entity-type'  => 'All scoped entity types',
            'actions'          => 'Actions',
            'actions-info'     => 'View reads records, Edit changes, retires, merges and creates records below the root, Approve decides held changes. Edit and Approve include View.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Type',
            'principal'         => 'Granted To',
            'missing-principal' => 'Deleted',
            'entity-type'       => 'Entity Type',
            'root'              => 'Subtree Root',
            'actions'           => 'Actions',
            'edit'              => 'Edit',
            'delete'            => 'Delete',
        ],
        'role-card' => [
            'title'  => 'Master Data Access',
            'info'   => 'Which records of scoped entity types users with this role reach. Each grant covers its root record and everything below it.',
            'empty'  => 'This role has no master data access grants yet.',
            'remove' => 'Remove',
            'add'    => 'Add a grant',
        ],
        'entity-type' => [
            'is-scoped'      => 'Restrict Access by Subtree',
            'is-scoped-info' => 'When on, users only reach records of this type inside the subtrees granted to their role or to them on the Data Access screen. Leave off for shared reference data.',
        ],
        'validation' => [
            'record-not-found'    => 'Choose a record you can see as the subtree root.',
            'parent-out-of-scope' => 'Choose a parent inside a subtree where you have edit access.',
        ],
        'create-success' => 'Data access grant added.',
        'update-success' => 'Data access grant updated.',
        'delete-success' => 'Data access grant deleted.',
    ],

    'install' => [
        'migrating' => 'Running the master data migrations.',
        'demo'      => 'Installing the manufacturing demo data.',
        'done'      => 'Master data management is installed. Active modules: :modules.',
    ],
];
