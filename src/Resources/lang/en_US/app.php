<?php

return [
    'menu' => [
        'master-data'    => 'Master Data',
        'records'        => 'Records',
        'entity-types'   => 'Entity Types',
        'source-systems' => 'Source Systems',
        'icons'          => 'Icons',
        'reference-data' => 'Reference Data',
        'matching'       => 'Matching',
        'crosswalk'      => 'Crosswalk Lookup',
        'quality'        => 'Data Quality',
        'approvals'      => 'Approvals',
    ],

    'acl' => [
        'master-data'  => 'Master Data',
        'entity-types' => [
            'index'      => 'Entity Types',
            'create'     => 'Create',
            'edit'       => 'Edit',
            'delete'     => 'Delete',
            'governance' => 'Change Data Owner and Approval',
        ],
        'source-systems' => [
            'index'  => 'Source Systems',
            'create' => 'Create',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ],
        'icons' => [
            'index'  => 'Icons',
            'create' => 'Create',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ],
        'records' => [
            'index'   => 'Records',
            'view'    => 'View',
            'create'  => 'Create',
            'edit'    => 'Edit',
            'delete'  => 'Delete',
            'merge'   => 'Merge',
            'approve' => 'Approve Changes',
            'erase'   => 'Erase Permanently',
        ],
        'code-lists' => [
            'index'  => 'Reference Data',
            'create' => 'Create',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ],
        'matching' => [
            'index'  => 'Matching',
            'create' => 'Add Incoming Rows',
            'edit'   => 'Accept, Create or Reject',
        ],
        'crosswalk' => [
            'index' => 'Crosswalk Lookup',
        ],
        'quality' => [
            'index'   => 'Data Quality',
            'scan'    => 'Run Quality Scans',
            'resolve' => 'Resolve or Ignore Issues',
        ],
        'approvals' => [
            'index' => 'Approvals',
        ],
    ],

    'aliases' => [
        'create-success' => 'Alias created successfully.',
        'update-success' => 'Alias updated successfully.',
        'delete-success' => 'Alias deleted successfully.',

        'index' => [
            'title'      => 'Aliases',
            'info'       => 'Map the terms that source systems send to a code or canonical value.',
            'create-btn' => 'Create Alias',
        ],

        'create' => [
            'title' => 'Create Alias',
        ],

        'edit' => [
            'title' => 'Edit Alias',
        ],

        'form' => [
            'scope-type'           => 'Scope Type',
            'select-scope-type'    => 'Select a scope type',
            'scope'                => 'Scope',
            'code-list'            => 'Code List',
            'select-code-list'     => 'Select a code list',
            'field'                => 'Field',
            'select-field'         => 'Select a field',
            'term'                 => 'Term',
            'target'               => 'Target',
            'target-info'          => 'The code or canonical value the term resolves to.',
            'locale'               => 'Locale',
            'select-locale'        => 'Any locale',
            'source-system'        => 'Source System',
            'select-source-system' => 'Any source system',
            'save-btn'             => 'Save Alias',
        ],

        'scopes' => [
            'code_list' => 'Code List',
            'field'     => 'Field',
            'global'    => 'Global',
        ],

        'datagrid' => [
            'term'          => 'Term',
            'target'        => 'Target',
            'scope-type'    => 'Scope Type',
            'scope'         => 'Scope',
            'locale'        => 'Locale',
            'source-system' => 'Source System',
            'edit'          => 'Edit',
            'delete'        => 'Delete',
        ],

        'resolve' => [
            'title'      => 'Resolve a Term',
            'info'       => 'Check what a term sent by a source system resolves to. Aliases of the chosen source system win over general ones, and code list aliases over global ones.',
            'any-system' => 'Any source system',
            'btn'        => 'Resolve',
            'found'      => '":term" resolves to :target.',
            'not-found'  => 'No alias matches ":term", so it is kept as sent.',
        ],

        'validation' => [
            'empty-term' => 'The term must contain at least one letter or digit.',
            'duplicate'  => 'An alias for this term already exists in the same scope and source system.',
        ],
    ],

    'code-lists' => [
        'create-success'    => 'Code list created successfully.',
        'update-success'    => 'Code list updated successfully.',
        'delete-success'    => 'Code list deleted successfully.',
        'delete-in-use'     => 'This code list cannot be deleted because a field uses it.',
        'version-not-draft' => 'Codes can only be changed in a draft version.',

        'tabs' => [
            'code-lists' => 'Code Lists',
            'aliases'    => 'Aliases',
        ],

        'index' => [
            'title'      => 'Code Lists',
            'create-btn' => 'Create Code List',
        ],

        'create' => [
            'title'    => 'Create Code List',
            'save-btn' => 'Save Code List',
        ],

        'edit' => [
            'title'    => 'Edit Code List',
            'save-btn' => 'Save Code List',
        ],

        'form' => [
            'general'      => 'General',
            'settings'     => 'Settings',
            'code'         => 'Code',
            'owner'        => 'Owner',
            'is-versioned' => 'Versioned',
            'name'         => 'Name',
            'description'  => 'Description',
        ],

        'datagrid' => [
            'code'              => 'Code',
            'name'              => 'Name',
            'owner'             => 'Owner',
            'published-version' => 'Published Version',
            'is-versioned'      => 'Versioned',
            'none'              => 'None',
            'yes'               => 'Yes',
            'no'                => 'No',
            'edit'              => 'Edit',
            'delete'            => 'Delete',
        ],

        'versions' => [
            'title'           => 'Codes',
            'info'            => 'Codes are edited in a draft version and take effect when it is published.',
            'label'           => 'Version :version',
            'new-draft-btn'   => 'New Draft',
            'publish-btn'     => 'Publish',
            'create-success'  => 'Draft version :version created successfully.',
            'publish-success' => 'Version :version published successfully.',
            'draft-exists'    => 'This code list already has a draft version. Publish it before creating another.',
            'confirm-title'   => 'Publish this version?',
            'confirm-message' => 'Version :version takes effect today and replaces the version in effect. Its codes can no longer be changed.',
            'effective'       => 'Effective :from to :to',
            'effective-open'  => 'Effective from :from',
            'read-only'       => 'This version is :status, so its codes are read-only. Create a new draft to make changes.',

            'statuses' => [
                'draft'     => 'Draft',
                'published' => 'Published',
                'retired'   => 'Retired',
            ],
        ],

        'publish' => [
            'not-draft' => 'Only a draft version can be published.',
        ],
    ],

    'codes' => [
        'create-success' => 'Code created successfully.',
        'update-success' => 'Code updated successfully.',
        'delete-success' => 'Code deleted successfully.',
        'create-btn'     => 'Add Code',

        'create' => [
            'title' => 'Add Code',
        ],

        'edit' => [
            'title' => 'Edit Code',
        ],

        'form' => [
            'general'        => 'General',
            'labels'         => 'Labels',
            'code'           => 'Code',
            'label'          => 'Label',
            'parent-code'    => 'Parent Code',
            'sort'           => 'Sort Order',
            'is-active'      => 'Active',
            'effective-from' => 'Effective From',
            'effective-to'   => 'Effective To',
            'save-btn'       => 'Save Code',
        ],

        'datagrid' => [
            'code'           => 'Code',
            'label'          => 'Label',
            'parent-code'    => 'Parent Code',
            'sort'           => 'Sort Order',
            'is-active'      => 'Active',
            'effective-from' => 'Effective From',
            'effective-to'   => 'Effective To',
            'yes'            => 'Yes',
            'no'             => 'No',
            'edit'           => 'Edit',
            'delete'         => 'Delete',
        ],
    ],

    'dashboard' => [
        'title'        => 'Master Data',
        'empty'        => 'There are no active entity types yet.',
        'record-count' => '{0} No records|{1} :count record|[2,*] :count records',
        'inside'       => 'Inside: :types',
        'top-level'    => 'Top level',
        'standalone'   => 'Standalone',
    ],

    'entity-types' => [
        'create-success'      => 'Entity type created successfully.',
        'update-success'      => 'Entity type updated successfully.',
        'delete-success'      => 'Entity type deleted successfully.',
        'delete-has-records'  => 'This entity type still has records and cannot be deleted.',
        'mass-delete-success' => 'Entity types deleted: :count.',
        'mass-delete-skipped' => 'Not deleted because they still have records: :codes.',

        'index' => [
            'title'      => 'Entity Types',
            'create-btn' => 'Create Entity Type',
        ],

        'create' => [
            'title'    => 'Create Entity Type',
            'save-btn' => 'Save Entity Type',
        ],

        'quick-create' => [
            'code-info'             => 'Lowercase letters, digits and underscores, starting with a letter. It cannot be changed after saving.',
            'code-pattern-info'     => 'Use {seq} or {seq:6} for the running number and {TYPE} for the type code, for example {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Records arrive from several source systems and are matched and merged into one golden record.',
                'centralized'   => 'Records are created and maintained here, then published to the systems that use them.',
            ],
        ],

        'edit' => [
            'title'          => 'Edit Entity Type',
            'save-btn'       => 'Save Entity Type',
            'schema-version' => 'Schema v:version',
        ],

        'icons' => [
            'dashboard'     => 'Dashboard',
            'catalog'       => 'Catalog',
            'product'       => 'Product',
            'attribute'     => 'Attribute',
            'channel'       => 'Channel',
            'folder'        => 'Folder',
            'file'          => 'File',
            'configuration' => 'Configuration',
            'setting'       => 'Settings',
            'processing'    => 'Processing',
            'data-transfer' => 'Data Transfer',
            'language'      => 'Language',
            'calendar'      => 'Calendar',
            'image'         => 'Image',
            'star'          => 'Star',
            'notification'  => 'Notification',
            'information'   => 'Information',
            'magic-ai'      => 'Magic AI',
        ],

        'form' => [
            'general'                   => 'General',
            'basics'                    => 'Basics',
            'identity'                  => 'Identity',
            'hierarchy'                 => 'Hierarchy',
            'governance'                => 'Governance',
            'quality-matching'          => 'Quality & Matching',
            'code'                      => 'Code',
            'name'                      => 'Name',
            'description'               => 'Description',
            'icon'                      => 'Icon',
            'select-icon'               => 'Select an icon',
            'no-icon'                   => 'No icon',
            'manage-icons'              => 'Manage icons',
            'label-field-code'          => 'Label Field Code',
            'status'                    => 'Status',
            'sort-order'                => 'Sort Order',
            'sort-order-info'           => 'Lower numbers are listed first, from 0 to 9999; types with the same number are listed by code.',
            'mastering-style'           => 'Mastering Style',
            'code-strategy'             => 'Code Strategy',
            'code-pattern'              => 'Code Pattern',
            'code-pattern-preview'      => 'Next codes',
            'code-pattern-missing-seq'  => 'Add {seq} or {seq:6} so every record gets its own number.',
            'is-hierarchical'           => 'Hierarchical',
            'allowed-parents'           => 'Allowed Parent Types',
            'approval-required'         => 'Approval Required',
            'sla-hours'                 => 'SLA (hours)',
            'sla-review'                => 'Review Tasks',
            'sla-approval'              => 'Approval Tasks',
            'quality-info'              => 'Changes here apply to existing records at the next quality scan.',
            'completeness-scope'        => 'Completeness Counts',
            'required-systems'          => 'Required Source Systems',
            'matching-info'             => 'Incoming source rows are matched to existing records in the match inbox.',
            'auto-match-threshold'      => 'Auto Match Threshold',
            'auto-match-off'            => 'Off',
            'auto-match-threshold-info' => 'Leave empty to review every row. From 0.5 to 1: a row whose best suggestion scores at least this, with no other suggestion as high, is accepted automatically. Types that require approval still hold the link for approval.',
            'match-on-write'            => 'Check for Duplicates on Save',
            'match-on-write-info'       => 'Each saved record is checked against the other records in the background, using the match rules. Turn off to find duplicates only in quality scans.',
            'data-owner'                => 'Data Owner',
            'no-owner'                  => 'No owner',
            'owner-option'              => ':name (:email)',
            'data-owner-info'           => 'A data owner who may also approve changes writes this type\'s records without approval and approves the changes of others.',
            'governance-locked'         => 'Only admins allowed to change data owner and approval can change it.',
            'governance-forbidden'      => 'You are not allowed to change the data owner or approval of this entity type.',
        ],

        'completeness-scopes' => [
            'all'      => 'All active fields',
            'required' => 'Required fields only',
        ],

        'mastering-styles' => [
            'consolidation' => 'Consolidation',
            'centralized'   => 'Centralized',
        ],

        'code-strategies' => [
            'pattern'           => 'Pattern',
            'identifier_fields' => 'Identifier Fields',
            'source_id'         => 'Source ID',
        ],

        'statuses' => [
            'draft'    => 'Draft',
            'active'   => 'Active',
            'archived' => 'Archived',
        ],

        'publish' => [
            'btn'             => 'Publish Schema',
            'confirm-title'   => 'Publish Schema',
            'confirm-message' => 'Publish the current fields as schema version :version? Published versions cannot be changed.',
            'success'         => 'Schema version :version published.',
        ],

        'permissions' => [
            'tab'            => 'Permissions',
            'title'          => 'Record Permissions',
            'info'           => 'Choose what each custom role may do with records of this entity type. Roles with all permissions are always allowed.',
            'empty'          => 'There are no custom roles yet.',
            'role'           => 'Role',
            'control-label'  => ':role: :action',
            'update-success' => 'Record permissions updated successfully.',

            'actions' => [
                'view'    => 'View',
                'create'  => 'Create',
                'edit'    => 'Edit',
                'delete'  => 'Delete',
                'merge'   => 'Merge',
                'approve' => 'Approve',
            ],
        ],

        'datagrid' => [
            'id'                   => 'ID',
            'code'                 => 'Code',
            'name'                 => 'Name',
            'mastering-style'      => 'Mastering Style',
            'status'               => 'Status',
            'schema-version'       => 'Schema Version',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Sort Order',
            'allowed-parents'      => 'Parent Types',
            'records-count'        => 'Records',
            'fields-count'         => 'Fields',
            'updated-at'           => 'Updated',
            'edit'                 => 'Edit',
            'delete'               => 'Delete',
        ],
    ],

    'fields' => [
        'tab'             => 'Fields',
        'title'           => 'Fields',
        'info'            => 'Define the fields the records of this entity type hold. Publish the schema to record a numbered version of them.',
        'create-btn'      => 'Add Field',
        'create-success'  => 'Field created successfully.',
        'update-success'  => 'Field updated successfully.',
        'delete-success'  => 'Field deleted successfully.',
        'archive-success' => 'The entity type has records, so the field was archived instead of deleted.',

        'create' => [
            'title' => 'Add Field',
        ],

        'edit' => [
            'title' => 'Edit Field',
        ],

        'form' => [
            'general'                => 'General',
            'code'                   => 'Code',
            'type'                   => 'Type',
            'select-type'            => 'Select a type',
            'group'                  => 'Group',
            'position'               => 'Position',
            'labels'                 => 'Labels',
            'label'                  => 'Label',
            'code-list'              => 'Code List',
            'select-code-list'       => 'Select a code list',
            'reference-type'         => 'Reference Entity Type',
            'reference-locked'       => 'Records already reference other records through this field, so its entity type and Multiple Values setting are fixed.',
            'select-reference-type'  => 'Select an entity type',
            'behavior'               => 'Behavior',
            'validation'             => 'Validation',
            'validation-type'        => 'Validation Type',
            'select-validation-type' => 'No validation type',
            'validation-regex'       => 'Regular Expression',
            'validation-min'         => 'Minimum',
            'validation-max'         => 'Maximum',
            'validation-max-length'  => 'Maximum Length',
            'save-btn'               => 'Save Field',

            'flags' => [
                'is_required'        => 'Required',
                'is_unique'          => 'Unique',
                'is_identifier'      => 'Identifier',
                'value_per_locale'   => 'Value per Locale',
                'is_effective_dated' => 'Effective Dated',
                'is_searchable'      => 'Searchable',
                'multiple'           => 'Multiple Values',
                'is_archived'        => 'Archived',
            ],
        ],

        'validation-types' => [
            'number'  => 'Number',
            'email'   => 'Email',
            'decimal' => 'Decimal',
            'url'     => 'URL',
            'regex'   => 'Regular Expression',
        ],

        'types' => [
            'text'        => 'Text',
            'textarea'    => 'Text Area',
            'integer'     => 'Integer',
            'decimal'     => 'Decimal',
            'boolean'     => 'Boolean',
            'date'        => 'Date',
            'datetime'    => 'Date and Time',
            'email'       => 'Email',
            'url'         => 'URL',
            'select'      => 'Select',
            'multiselect' => 'Multiselect',
            'reference'   => 'Reference',
            'file'        => 'File',
            'image'       => 'Image',
        ],

        'datagrid' => [
            'code'       => 'Code',
            'label'      => 'Label',
            'type'       => 'Type',
            'required'   => 'Required',
            'searchable' => 'Searchable',
            'archived'   => 'Archived',
            'position'   => 'Position',
            'yes'        => 'Yes',
            'no'         => 'No',
            'edit'       => 'Edit',
            'delete'     => 'Delete',
        ],

        'validation' => [
            'invalid-code'        => 'The :attribute contains a code that is not in its code list.',
            'invalid-reference'   => 'The :attribute must only contain record identifiers.',
            'invalid-measurement' => 'The :attribute must have a numeric value and a unit.',
            'invalid-regex'       => 'The :attribute is not a valid regular expression.',
            'reference-locked'    => 'The :attribute cannot change while records reference other records through this field.',
        ],
    ],

    'relation-types' => [
        'tab'            => 'Relations',
        'title'          => 'Relation Types',
        'info'           => 'Define how records of this entity type link to records of other types. Reference fields add a system relation type of their own.',
        'create-btn'     => 'Add Relation Type',
        'create-success' => 'Relation type created successfully.',
        'update-success' => 'Relation type updated successfully.',
        'delete-success' => 'Relation type deleted successfully.',
        'system-info'    => 'Managed by its reference field',

        'create' => [
            'title' => 'Add Relation Type',
        ],

        'edit' => [
            'title' => 'Edit Relation Type',
        ],

        'form' => [
            'code'               => 'Code',
            'target-type'        => 'Target Entity Type',
            'select-target-type' => 'Select an entity type',
            'cardinality'        => 'Cardinality',
            'labels'             => 'Labels',
            'label'              => 'Label',
            'save-btn'           => 'Save Relation Type',
        ],

        'cardinalities' => [
            'one'  => 'One record',
            'many' => 'Many records',
        ],

        'datagrid' => [
            'code'        => 'Code',
            'label'       => 'Label',
            'target-type' => 'Target Entity Type',
            'cardinality' => 'Cardinality',
            'system'      => 'System',
            'yes'         => 'Yes',
            'no'          => 'No',
            'edit'        => 'Edit',
            'delete'      => 'Delete',
        ],

        'validation' => [
            'system-type'   => 'This relation type belongs to a reference field. Change the field instead.',
            'in-use'        => 'This relation type still has relations between records, so it cannot be deleted.',
            'type-mismatch' => 'The records do not match the entity types of the relation type.',
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
        'title'                => 'Hierarchy',
        'ancestors'            => 'Path',
        'trail-separator'      => '/',
        'root'                 => 'This record is at the top of its hierarchy.',
        'children'             => 'Children',
        'children-limited'     => 'Showing the first :count children.',
        'empty-children'       => 'This record has no children.',
        'empty-children-types' => ':children records can sit under this :type.',
        'empty-children-none'  => 'No active entity type lists :type as an allowed parent.',
        'change-parent'        => 'Change Parent',
        'parent'               => 'Parent',
        'revision'             => 'Revision',
        'select-parent'        => 'Select a parent',
        'no-parent'            => 'No parent (root)',
        'hidden-parent'        => 'Current parent (not visible to you)',
        'candidates-limit'     => 'Only the first :count possible parents are listed.',
        'save'                 => 'Save',
        'move-success'         => 'Parent changed. The records below it are being moved.',
        'browse-children'      => 'Browse :type below',

        'validation' => [
            'not-hierarchical'        => 'Records of this entity type cannot have a parent.',
            'parent-type-not-allowed' => 'Records of this entity type cannot be placed under the selected parent type.',
            'cycle'                   => 'A record cannot be placed under itself or one of its descendants.',
            'parent-not-active'       => 'The parent must be a draft or active record.',
            'parent-not-found'        => 'The selected parent record was not found.',
            'rewrite-pending'         => 'An earlier move in this part of the hierarchy is still being applied. Try again in a moment.',
            'path-too-long'           => 'The hierarchy would become too deep at this position.',
        ],
    ],

    'lineage' => [
        'title'  => 'Lineage',
        'system' => 'System',
        'never'  => 'Not recorded',

        'path' => [
            'title' => 'Hierarchy Path',
            'root'  => 'This record is at the top of its hierarchy.',
        ],

        'authorship' => [
            'title'      => 'Created and Updated',
            'created-at' => 'Created At',
            'created-by' => 'Created By',
            'updated-at' => 'Updated At',
            'updated-by' => 'Updated By',
        ],

        'schema' => [
            'title'           => 'Schema Version',
            'validated'       => 'Validated Against',
            'version'         => 'Version :version',
            'current-version' => 'Current Published Version',
            'published-at'    => 'Published At',
            'published-by'    => 'Published By',
            'current'         => 'Current',
            'outdated'        => 'Older than current',
            'none'            => 'No published schema version when last saved',
            'unpublished'     => 'Not published yet',
        ],

        'sources' => [
            'title'        => 'Source Systems',
            'empty'        => 'No source system has linked this record yet.',
            'system'       => 'Source System',
            'external-id'  => 'External ID',
            'status'       => 'Status',
            'attached-at'  => 'Attached At',
            'last-seen-at' => 'Last Seen At',
        ],

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

            'states' => [
                'never_run'  => 'Not exported yet',
                'pending'    => 'Changed since last run',
                'included'   => 'Included in last run',
                'up_to_date' => 'Up to date',
            ],
        ],

        'changes' => [
            'title'   => 'Recent Changes',
            'empty'   => 'No changes have been recorded for this record.',
            'limited' => 'Showing the latest :count changes.',
            'version' => 'Version',
            'event'   => 'Event',
            'user'    => 'By',
            'at'      => 'At',
            'changed' => 'Changed',

            'events' => [
                'created'  => 'Created',
                'updated'  => 'Updated',
                'deleted'  => 'Deleted',
                'restored' => 'Restored',
            ],
        ],

        'attributes' => [
            'code'           => 'Code',
            'label'          => 'Label',
            'status'         => 'Status',
            'parent_id'      => 'Parent',
            'schema_version' => 'Schema version',
            'merged_into_id' => 'Merged into',
        ],
    ],

    'records' => [
        'stale-revision'        => 'This record was changed by someone else. Reload it and try again.',
        'not-editable'          => 'This record is retired, merged or erased and can no longer be changed.',
        'empty-values'          => 'This record has no values.',
        'empty-xrefs'           => 'This record has no cross-references.',
        'create-success'        => 'Record created successfully.',
        'update-success'        => 'Record updated successfully.',
        'retire-success'        => 'Record retired. It stays readable but can no longer be changed.',
        'change-pending'        => 'The change was submitted for approval.',
        'owner-badge'           => 'Owner: :name',
        'option-label'          => ':code · :label · :type',
        'option-label-no-label' => ':code · :type',
        'code-label'            => ':code · :label',

        'validation' => [
            'unique' => 'The :attribute has already been taken by another record.',
        ],

        'index' => [
            'title'       => ':type Records',
            'create-btn'  => 'Create Record',
            'switch-type' => 'Switch Entity Type',
            'top-level'   => 'Top Level',
        ],

        'create' => [
            'title'    => 'Create :type Record',
            'save-btn' => 'Save Record',
        ],

        'edit' => [
            'title'     => 'Record :code',
            'save-btn'  => 'Save Record',
            'read-only' => 'This record is :status and can no longer be changed.',
        ],

        'retire' => [
            'btn'             => 'Retire',
            'confirm-title'   => 'Retire this record?',
            'confirm-message' => 'A retired record stays readable with its history and cross-references, but it can no longer be edited.',
        ],

        'merge' => [
            'btn'                     => 'Merge Duplicate',
            'title'                   => 'Merge a duplicate into :code',
            'info'                    => 'Choose the duplicate record to fold into :code. This record keeps its code and identity; the duplicate is marked as merged.',
            'duplicate'               => 'Duplicate record',
            'select-duplicate'        => 'Search by code or label',
            'compare'                 => 'Compare',
            'moves-info'              => 'Pick which value :survivor keeps for each field. The source IDs, child records and relations of :merged move to :survivor.',
            'no-values'               => 'Neither record has field values to compare.',
            'field'                   => 'Field',
            'survivor-value'          => ':code (keeps identity)',
            'merged-value'            => ':code (duplicate)',
            'keep'                    => 'Keep value from',
            'keep-survivor'           => 'This record',
            'keep-merged'             => 'Duplicate',
            'submit'                  => 'Merge Records',
            'success'                 => 'Record :code was merged into this record.',
            'empty'                   => 'No duplicates have been merged into this record.',
            'empty-info'              => 'Use Merge Duplicate when the same item was created twice.',
            'merged-at'               => 'Merged At',
            'unmerge'                 => 'Unmerge',
            'unmerge-confirm-title'   => 'Unmerge this record?',
            'unmerge-confirm-message' => 'The record becomes active again and gets back its source IDs, child records and relations. Values changed on this record since the merge are kept.',
            'unmerge-success'         => 'Record :code was unmerged.',
            'same-record'             => 'A record cannot be merged into itself.',
            'different-type'          => 'Only records of the same entity type can be merged.',
            'not-mergeable'           => 'Only draft or active records can be merged.',
            'ancestor'                => 'A parent record cannot be merged into one of its own descendants.',
            'too-many-children'       => 'The duplicate has more than :count child records. Move some of them before merging.',
            'not-merged'              => 'This record is not merged into another record.',
            'survivor-missing'        => 'The record this one was merged into is no longer available.',
        ],

        'reactivate' => [
            'btn'             => 'Reactivate',
            'confirm-title'   => 'Reactivate this record?',
            'confirm-message' => 'The record becomes active again and can be edited.',
            'success'         => 'Record reactivated.',
            'not-retired'     => 'Only a retired record can be reactivated.',
            'parent-retired'  => 'This record cannot be reactivated while its parent is retired. Reactivate the parent first.',
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

        'form' => [
            'general'           => 'General',
            'values'            => 'Values',
            'other-group'       => 'Other',
            'code-pattern'      => 'Generated on save from the pattern :pattern.',
            'code-identifiers'  => 'Built on save from: :fields.',
            'label-from-field'  => 'The label follows the :field field. Change :field to rename the record.',
            'no-fields'         => 'This entity type has no fields yet. Add fields to the entity type to edit values here.',
            'other-values'      => 'Other stored values',
            'other-values-info' => 'Values of archived fields or set by another system. They are kept as they are.',
            'no-control'        => 'This field is not edited on this form yet.',
            'locale'            => 'Locale: :locale',
            'select-option'     => 'Select an option',
            'select-record'     => 'Search for a record',
            'measurement-value' => 'Value',
            'measurement-unit'  => 'Unit',
        ],

        'tabs' => [
            'general'          => 'General',
            'values'           => 'Values',
            'cross-references' => 'Cross-references',
            'relations'        => 'Relations',
            'quality'          => 'Data Quality',
            'quality-count'    => 'Data Quality (:count)',
            'approvals'        => 'Approvals',
            'approvals-count'  => 'Approvals (:count)',
            'merges'           => 'Merged Records',
            'merges-count'     => 'Merged Records (:count)',
        ],

        'fields' => [
            'type'       => 'Entity Type',
            'code'       => 'Code',
            'label'      => 'Label',
            'status'     => 'Status',
            'updated-at' => 'Updated At',
        ],

        'relations' => [
            'outbound'             => 'Points to',
            'inbound'              => 'Referenced by',
            'empty-outbound'       => 'This record does not point to any record.',
            'empty-inbound'        => 'No record points to this record.',
            'none-configured'      => 'No relation types are set up for :type.',
            'none-configured-info' => 'Relations link records side by side, e.g. a filler feeds a checkweigher. Add a relation type under Entity Types.',
            'possible'             => 'Possible relations: :types.',
            'no-types-outbound'    => 'No relation type starts from this entity type.',
            'no-types-inbound'     => 'No relation type points to this entity type.',
            'type'                 => 'Relation',
        ],

        'values' => [
            'key'    => 'Attribute',
            'locale' => 'Locale',
            'value'  => 'Value',
        ],

        'xrefs' => [
            'system'                 => 'Source System',
            'external-id'            => 'External ID',
            'status'                 => 'Status',
            'last-seen'              => 'Last Seen',
            'actions'                => 'Actions',
            'link-btn'               => 'Link Source ID',
            'link-title'             => 'Link a Source System ID',
            'link-info'              => 'External IDs are matched exactly, including upper and lower case.',
            'select-system'          => 'Select a source system',
            'save'                   => 'Link',
            'link-success'           => 'Source ID linked to this record.',
            'unlink'                 => 'Unlink',
            'unlink-success'         => 'Source ID unlinked. It is kept as retired in the lineage.',
            'unlink-confirm-title'   => 'Unlink this source ID?',
            'unlink-confirm-message' => 'The ID will no longer resolve to this record. Another record may link it afterwards.',
            'history-label'          => 'Source ID :system: :id',
        ],

        'xref-statuses' => [
            'active'  => 'Active',
            'retired' => 'Retired',
        ],

        'statuses' => [
            'draft'   => 'Draft',
            'active'  => 'Active',
            'retired' => 'Retired',
            'merged'  => 'Merged',
            'erased'  => 'Erased',
        ],

        'datagrid' => [
            'code'           => 'Code',
            'label'          => 'Label',
            'status'         => 'Status',
            'external-ids'   => 'IDs',
            'completeness'   => 'Completeness',
            'dq-score'       => 'Quality Score',
            'updated-at'     => 'Updated At',
            'view'           => 'View',
            'edit'           => 'Edit',
            'retire'         => 'Retire',
            'reactivate'     => 'Reactivate',
            'erase'          => 'Erase Permanently',
            'invalid-filter' => 'The filter on :field does not fit the values of that field.',
            'parent'         => 'Parent',
            'top-level'      => 'Top level',
            'children'       => 'Children',
            'view-children'  => 'Show the :count records below this one',
        ],
    ],

    'icons' => [
        'create-success' => 'Icon created successfully.',
        'update-success' => 'Icon updated successfully.',
        'delete-success' => 'Icon deleted successfully.',
        'delete-in-use'  => '{1} This icon is shown by one entity type and cannot be deleted.|[2,*] This icon is shown by :count entity types and cannot be deleted.',

        'index' => [
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

    'source-systems' => [
        'create-success'   => 'Source system created successfully.',
        'update-success'   => 'Source system updated successfully.',
        'delete-success'   => 'Source system deleted successfully.',
        'delete-has-xrefs' => 'This source system still has cross-references and cannot be deleted.',
        'option-label'     => ':name (:code)',
        'active'           => 'Active',
        'inactive'         => 'Inactive',

        'index' => [
            'title'      => 'Source Systems',
            'create-btn' => 'Create Source System',
        ],

        'create' => [
            'title'    => 'Create Source System',
            'save-btn' => 'Save Source System',
        ],

        'edit' => [
            'title'    => 'Edit Source System',
            'save-btn' => 'Save Source System',
        ],

        'form' => [
            'general'                 => 'General',
            'settings'                => 'Settings',
            'code'                    => 'Code',
            'name'                    => 'Name',
            'type'                    => 'Type',
            'priority'                => 'Priority',
            'contact-owner'           => 'Contact Owner',
            'is-active'               => 'Active',
            'requires-approval'       => 'Requires Approval',
            'external-id-is-personal' => 'External IDs Are Personal Data',
        ],

        'types' => [
            'erp'   => 'ERP',
            'mes'   => 'MES',
            'cmms'  => 'CMMS',
            'crm'   => 'CRM',
            'other' => 'Other',
        ],

        'datagrid' => [
            'id'        => 'ID',
            'code'      => 'Code',
            'name'      => 'Name',
            'type'      => 'Type',
            'priority'  => 'Priority',
            'is-active' => 'Status',
            'edit'      => 'Edit',
            'delete'    => 'Delete',
        ],
    ],

    'matching' => [
        'submit-success'    => 'Incoming row added to the match inbox.',
        'accept-success'    => 'Incoming row linked to the record.',
        'create-success'    => 'Record created from the incoming row and linked.',
        'reject-success'    => 'Incoming row rejected.',
        'already-linked'    => 'The external id :id is already linked to a record in :system.',
        'already-resolved'  => 'This incoming row was already decided.',
        'wrong-entity-type' => 'The record belongs to another entity type.',
        'record-not-found'  => 'The record was not found.',

        'index' => [
            'title'   => 'Matching',
            'add-btn' => 'Add Incoming Row',
            'info'    => 'Rows from source systems whose IDs are not linked to a record yet. Review each row to link it, create a record or reject it.',
        ],

        'add' => [
            'title'    => 'Add Incoming Row',
            'info'     => 'Enter a row as a source system sends it. Give a code, a label or both; they are used to suggest matching records.',
            'save-btn' => 'Add to Inbox',
        ],

        'show' => [
            'title'                 => 'Incoming Row :id',
            'details'               => 'Incoming Row',
            'suggestions'           => 'Suggested Records',
            'no-suggestions'        => 'No existing record looks similar. Create a new record or reject the row.',
            'decision'              => 'Decision',
            'resolved'              => ':status by :admin on :date.',
            'resolved-record-label' => 'Linked record: :record',
            'score'                 => 'Score',
            'reason'                => 'Why',
            'accept'                => 'Accept',
            'accept-title'          => 'Link to this record?',
            'accept-message'        => 'The external id :id of :system will resolve to record :code.',
            'create'                => 'Create New Record',
            'create-title'          => 'Create a record from this row',
            'create-info'           => 'The record is created with the values below and the external id is linked to it.',
            'create-btn'            => 'Create and Link',
            'reject'                => 'Reject',
            'reject-title'          => 'Reject this incoming row?',
            'reject-message'        => 'Nothing is linked or created. The row can be submitted again later.',
            'unknown-admin'         => 'the system',
            'auto-matched'          => 'Auto-matched',
            'auto-pending'          => 'Matched automatically; the link waits for approval.',
        ],

        'fields' => [
            'system'      => 'Source System',
            'entity-type' => 'Entity Type',
            'external-id' => 'External ID',
            'code'        => 'Code',
            'label'       => 'Label',
            'record'      => 'Record',
            'status'      => 'Status',
            'top-score'   => 'Best Score',
            'submitted'   => 'Submitted At',
        ],

        'reasons' => [
            'exact_code'    => 'Same code',
            'exact_label'   => 'Same label',
            'compact_code'  => 'Same code apart from spacing',
            'compact_label' => 'Same label apart from spacing',
            'similar_code'  => 'Similar code',
            'similar_label' => 'Similar label',
            'rule'          => 'Match rule on :target',
        ],

        'statuses' => [
            'pending'  => 'Pending',
            'matched'  => 'Matched',
            'created'  => 'Created',
            'rejected' => 'Rejected',
        ],

        'datagrid' => [
            'system'       => 'Source System',
            'entity-type'  => 'Entity Type',
            'external-id'  => 'External ID',
            'label'        => 'Label',
            'top-score'    => 'Best Score',
            'status'       => 'Status',
            'submitted-at' => 'Submitted At',
            'review'       => 'Review',
            'decision'     => 'Decision',
            'auto'         => 'Auto-matched',
            'manual'       => 'Manual',
        ],
    ],

    'crosswalk' => [
        'title'       => 'Crosswalk Lookup',
        'info'        => 'Find the golden record behind a source system ID and see its IDs in every system.',
        'lookup-btn'  => 'Look Up',
        'not-found'   => 'No record is linked to :id in :system.',
        'found-label' => 'Golden record: :label',
        'summary'     => ':code · :type · :status',
        'ids'         => 'IDs in every system',
        'open-record' => 'Open record',

        'fields' => [
            'system'      => 'Source System',
            'entity-type' => 'Entity Type',
            'external-id' => 'External ID',
            'select'      => 'Select an option',
        ],
    ],

    'xrefs' => [
        'invalid-external-id' => 'The external id must be between 1 and 255 characters.',
        'conflict'            => 'The external id :id is already linked to another record in :system.',
    ],

    'api' => [
        'acl' => [
            'master-data'  => 'Master Data',
            'records'      => 'Records',
            'matching'     => 'Matching',
            'upsert'       => 'Upsert by Source System ID',
            'create'       => 'Create',
            'edit'         => 'Edit',
            'delete'       => 'Retire',
            'entity-types' => 'Entity Types',
            'code-lists'   => 'Code Lists',
        ],

        'code-list-not-found'      => 'The code list was not found.',
        'filter-not-searchable'    => 'The field ":field" cannot be filtered on. Only active searchable fields can.',
        'forbidden-record-write'   => 'Your role is not allowed to change records of this entity type.',
        'precondition-required'    => 'Send the record revision you read in the If-Match header.',
        'merged-record'            => 'This record was merged into another record. Send the change to the surviving record.',
        'idempotency-key-too-long' => 'The Idempotency-Key header may not be longer than 255 characters.',
        'idempotency-in-flight'    => 'A request with this Idempotency-Key is still being processed. Retry it shortly.',
        'idempotency-mismatch'     => 'This Idempotency-Key was already used for a different request.',
        'idempotency-pruned'       => 'Deleted :count expired idempotency keys.',

        'validation-failed' => 'Validation failed.',
        'change-held'       => 'The change is waiting for approval.',

        'xref-not-found'           => 'No record matches this external id.',
        'system-or-type-not-found' => 'The source system or entity type was not found.',
        'entity-type-not-found'    => 'The entity type was not found.',
        'record-not-found'         => 'The record was not found.',
        'forbidden-entity-type'    => 'Your role is not allowed to view records of this entity type.',
        'system-inactive'          => 'The source system is inactive, so it cannot submit rows.',
        'type-inactive'            => 'Rows can only be submitted for an active entity type.',

        'validation' => [
            'too-many-values' => 'The :attribute may hold at most :max entries.',
            'value-too-long'  => 'The :attribute may not be longer than :max characters.',
            'invalid-value'   => 'The :attribute must be a text, number, boolean or a list of them.',
        ],
    ],

    'quality' => [
        'resolve-success' => 'Issue marked as resolved.',
        'ignore-success'  => 'Issue ignored. Later scans keep it closed.',
        'not-open'        => 'This issue is already closed.',

        'index' => [
            'title'       => 'Data Quality',
            'info'        => 'Completeness and open issues per entity type. Issues close on their own once a later change fixes them.',
            'summary'     => 'Completeness by Entity Type',
            'issues'      => 'Issues',
            'empty'       => 'No active entity types yet.',
            'entity-type' => 'Entity Type',
            'records'     => 'Records',
            'average'     => 'Average Completeness',
            'complete'    => 'Complete',
            'complete-of' => ':complete of :records',
            'open-issues' => 'Open Issues',
            'errors'      => ':count errors',
            'warnings'    => ':count warnings',
            'last-scan'   => 'Last Scan',
            'never'       => 'Never',
            'actions'     => 'Actions',
        ],

        'scan' => [
            'btn'             => 'Run Quality Scan',
            'confirm-title'   => 'Run Quality Scan',
            'confirm-message' => 'Check every record of this entity type for completeness, validity, consistency and likely duplicates? The scan runs in the background.',
            'queued'          => 'Quality scan of :type queued. Results appear once it has run.',
            'done'            => 'Quality scan of :type checked :count records.',
            'unknown-type'    => 'No entity type has the code :code.',
        ],

        'completeness' => [
            'title'        => 'Completeness',
            'badge'        => 'Completeness :score%',
            'not-scored'   => 'n/a',
            'summary'      => ':filled of :total counted fields filled.',
            'missing-list' => 'Missing: :fields',
            'percent'      => ':score%',
            'none'         => 'This entity type has no fields to count.',
            'bands'        => [
                'complete' => 'Complete (100%)',
                'partial'  => 'Partial (50 to 99%)',
                'low'      => 'Low (below 50%)',
                'none'     => 'Not scored',
            ],
        ],

        'record' => [
            'issues'    => 'Open Issues',
            'no-issues' => 'No open issues on this record.',
        ],

        'rules' => [
            'validity'      => 'Validity',
            'hierarchy'     => 'Consistency: hierarchy',
            'required_xref' => 'Consistency: cross-reference',
            'duplicate'     => 'Likely duplicate',
        ],

        'messages' => [
            'validity'  => ':message',
            'hierarchy' => [
                'parent_type'   => 'Parent :parent is a :type record, which this entity type does not allow as a parent.',
                'parent_status' => 'Parent :parent is :status.',
            ],
            'required_xref' => 'No external id from the required source system :system.',
            'duplicate'     => 'Likely duplicate of :record (:score% similar label).',
        ],

        'severities' => [
            'error'   => 'Error',
            'warning' => 'Warning',
            'info'    => 'Info',
        ],

        'statuses' => [
            'open'     => 'Open',
            'resolved' => 'Resolved',
            'ignored'  => 'Ignored',
        ],

        'actions' => [
            'resolve'                 => 'Resolve',
            'ignore'                  => 'Ignore',
            'resolve-confirm-title'   => 'Resolve Issue',
            'resolve-confirm-message' => 'Mark this issue as resolved? A later scan reopens it if the problem is still there.',
            'ignore-confirm-title'    => 'Ignore Issue',
            'ignore-confirm-message'  => 'Ignore this issue? Later scans keep it closed.',
        ],

        'datagrid' => [
            'entity-type' => 'Entity Type',
            'record'      => 'Record',
            'rule'        => 'Rule',
            'message'     => 'Issue',
            'severity'    => 'Severity',
            'status'      => 'Status',
            'detected-at' => 'Detected At',
            'view-record' => 'View Record',
        ],
    ],

    'exports' => [
        'records' => [
            'title'          => 'Master Data Records',
            'published'      => 'Export published to :location.',
            'completed-info' => 'All selected master data records have been exported. Download the file or pick it up at the export destination.',

            'filters' => [
                'entity-type'                 => 'Entity Type',
                'changed-since-last-run'      => 'Changed Since Last Run',
                'changed-since-last-run-info' => 'Export only the records updated since the last completed run of this job. The first run exports every record.',
                'statuses'                    => 'Statuses',
                'statuses-info'               => 'Leave empty to export records in every status.',
            ],

            'formats' => [
                'jsonl' => 'JSON Lines (JSONL)',
                'csv'   => 'CSV',
            ],

            'errors' => [
                'entity-type-missing' => 'The entity type :code chosen for this export no longer exists.',
                'entity-type-invalid' => 'Choose an active entity type you may view.',
                'status-invalid'      => 'Choose only known record statuses.',
                'not-allowed'         => 'The admin who started this export may not view the records of :code.',
                'file-missing'        => 'The export file :path could not be read.',
                'publish-failed'      => 'The export could not be written to :path on the :disk disk.',
            ],
        ],
    ],

    'imports' => [
        'completed-info' => 'The import finished. Changes to entity types that require approval wait in the approvals inbox; download the log for the message of each held or refused row.',

        'records' => [
            'title' => 'Master Data Records',

            'columns' => [
                'code'        => 'Code',
                'label'       => 'Label',
                'status'      => 'Status',
                'parent-code' => 'Parent code',
                'external-id' => 'External ID',
            ],
        ],

        'xrefs' => [
            'title' => 'Master Data Crosswalk IDs',
        ],

        'code-lists' => [
            'title'          => 'Master Data Code Lists',
            'completed-info' => 'The codes were written to the draft version of the code list. Review the draft and publish it from the code list page.',
        ],

        'filters' => [
            'entity-type'        => 'Entity Type',
            'entity-type-info'   => 'The entity type whose records the file holds.',
            'source-system'      => 'Source System',
            'source-system-info' => 'The system the file comes from. Its external IDs are read from the external_id column.',
            'mapping'            => 'Column Mapping',
            'mapping-info'       => 'One line per renamed column: file column=field code, or field code-locale for a per-locale field, e.g. Asset Num=external_id and Description=name. Columns without a line are read by their own name.',
            'code-list'          => 'Code List',
            'code-list-info'     => 'The list to import into. Codes are written to its draft version, which you publish separately.',
        ],

        'summary' => [
            'held'    => 'Changes Held for Approval',
            'skipped' => 'Rows Refused While Importing',
            'info'    => 'Held changes wait in the approvals inbox. The log file names each held or refused row and the reason.',
        ],

        'messages' => [
            'held'            => 'Row :row: change held for approval as change request #:reference.',
            'skipped'         => 'Row :row: not imported. :message',
            'code-list-draft' => 'Codes written to draft version :version of code list :code.',
        ],

        'errors' => [
            'delete-not-supported'              => 'Master data imports only create and update. Delete records, crosswalk IDs and codes from their admin pages.',
            'no-author'                         => 'This import has no admin who started it, so it cannot be written.',
            'entity-type-missing'               => 'The entity type :code chosen for this import does not exist or is not active.',
            'entity-type-invalid'               => 'Choose an active entity type whose records you may create or edit.',
            'source-system-missing'             => 'The source system :code chosen for this import does not exist or is not active.',
            'source-system-invalid'             => 'Choose an active source system.',
            'external-id-without-source-system' => 'The file has an external ID column (:column). Choose the source system it comes from, or remove the column.',
            'records-not-allowed'               => 'The admin who started this import may not write records of :code.',
            'create-not-allowed'                => 'You may not create records of this entity type.',
            'edit-not-allowed'                  => 'You may not edit records of this entity type.',
            'code-not-found'                    => 'No record has the code :code, and this entity type generates the codes of new records.',
            'record-not-found'                  => 'No record you may edit has the code :code.',
            'parent-not-found'                  => 'No record you may see has the parent code :code.',
            'already-pending'                   => 'A change to this record is already waiting for approval.',
            'mapping-invalid-lines'             => 'Write each mapping line as column=field code. Check line(s) :lines.',
            'mapping-unknown-targets'           => 'These mapping targets are not columns or active fields of the entity type: :targets.',
            'code-list-missing'                 => 'The code list :code chosen for this import does not exist.',
            'code-list-invalid'                 => 'Choose an existing code list you may edit.',
            'code-list-not-allowed'             => 'The admin who started this import may not edit the code list :code.',
            'parent-code-not-found'             => 'The parent code :code is not in the draft version of the code list.',
            'default-label-required'            => 'A new code needs a label in :locale.',
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

    'approvals' => [
        'new-record'      => 'New record',
        'approve-success' => 'Change approved and applied.',
        'reject-success'  => 'Change rejected.',
        'not-pending'     => 'This change request was already decided.',
        'conflict'        => 'The record changed since this request was made, so the change was not applied. The request is now stale; ask the requester to submit it again.',

        'index' => [
            'title' => 'Approvals',
            'info'  => 'Record changes to entity types that require approval wait here until an approver or the data owner decides them.',
        ],

        'show' => [
            'title'         => 'Change Request #:id',
            'changes'       => 'Proposed Changes',
            'no-changes'    => 'This request changes no values.',
            'details'       => 'Details',
            'approve'       => 'Approve',
            'approve-title' => 'Approve this change?',
            'approve-info'  => 'The change is applied to the record as proposed. A record changed since the request is refused.',
            'approve-btn'   => 'Approve and Apply',
            'reject'        => 'Reject',
            'reject-title'  => 'Reject this change?',
            'reject-info'   => 'The record stays as it is. Tell the requester why the change was rejected.',
            'reject-btn'    => 'Reject Change',
            'conflict'      => 'The record changed since this request was made (revision :base, now :current). Approving it will be refused.',
            'stale-info'    => 'The record changed before this request was approved, so it was not applied.',
            'waiting'       => 'This change waits for an approver or the data owner.',
        ],

        'diff' => [
            'field'          => 'Field',
            'before'         => 'Before',
            'after'          => 'Proposed',
            'parent'         => 'Parent',
            'generated-code' => 'Generated on approval',
            'hidden-record'  => 'Record not visible to you',
            'xref'           => 'Source ID (:system)',
        ],

        'already-pending' => 'A change of this kind is already waiting for approval. It must be approved or rejected first.',

        'notification' => [
            'title'                 => 'Approval needed: change request #:id',
            'description'           => ':requester submitted a ":operation" change to :type for your approval.',
            'description-automated' => 'An automatic ":operation" change to :type is waiting for your approval.',
        ],

        'fields' => [
            'comment'             => 'Comment',
            'comment-placeholder' => 'Add a note for the requester',
            'data-owner'          => 'Data Owner',
            'base-revision'       => 'Requested at Revision',
            'decided-at'          => 'Decided At',
        ],

        'datagrid' => [
            'id'           => 'ID',
            'entity-type'  => 'Entity Type',
            'record'       => 'Record',
            'operation'    => 'Change',
            'status'       => 'Status',
            'requested-by' => 'Requested By',
            'requested-at' => 'Requested At',
            'decided-by'   => 'Decided By',
            'review'       => 'Review',
        ],

        'operations' => [
            'create'      => 'Create',
            'update'      => 'Update',
            'retire'      => 'Retire',
            'reactivate'  => 'Reactivate',
            'link_xref'   => 'Link Source ID',
            'unlink_xref' => 'Unlink Source ID',
            'merge'       => 'Merge',
            'unmerge'     => 'Unmerge',
        ],

        'statuses' => [
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'stale'    => 'Stale',
        ],

        'badge' => [
            'pending' => '{1} 1 change pending approval|[2,*] :count changes pending approval',
            'open'    => 'Open the pending change request',
        ],

        'record' => [
            'empty' => 'No change requests for this record yet.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Manufacturing demo ready: :types entity types, :systems source systems, :records records in a site tree, :xrefs cross-references, :lists code lists, :fields fields, :aliases aliases.',
        'invalid-template' => 'The template :name could not be read or has an unsupported format version.',
        'module-disabled'  => 'Master Data is switched off, so the seeded records stay hidden. Enable it in System Settings > General > Platform > Modules by choosing Master Data or Both.',
    ],

    'help' => [
        'source-systems' => [
            'code'                    => 'Short unique ID used in import files and the API, e.g. sap_erp. It cannot be changed later.',
            'name'                    => 'The name people know the system by, e.g. SAP ERP or Plant MES.',
            'type'                    => 'What kind of system this is, e.g. ERP for planning or CMMS for maintenance.',
            'priority'                => 'Lower numbers are listed first wherever you pick a source system, e.g. 10 for your main ERP.',
            'contact-owner'           => 'Person or team to ask about this system\'s data, e.g. erp-support@plant.example.',
            'is-active'               => 'Imports and API calls from an inactive system are refused. Its existing links are kept.',
            'requires-approval'       => 'A note for stewards. Changes wait for approval only when their entity type requires approval.',
            'external-id-is-personal' => 'Turn on when this system\'s IDs identify people, e.g. employee numbers. Stored as a privacy note.',
        ],

        'entity-types' => [
            'code'               => 'Short unique ID used in files and the API, e.g. production_line. It cannot be changed later.',
            'icon'               => 'Picture shown next to this type on the dashboard and in the entity type list.',
            'label-field-code'   => 'The field whose value becomes each record\'s label, e.g. the Name field.',
            'status'             => 'Only Active types show on the dashboard and accept new records, imports and API data.',
            'mastering-style'    => 'Consolidation merges records sent by source systems; Centralized means records are created and kept here.',
            'name'               => 'The name shown on screens and the dashboard, e.g. Production Line.',
            'description'        => 'Optional note explaining what this type holds, for other users.',
            'code-strategy'      => 'How each record gets its code: from the pattern, from its identifier fields, or typed in.',
            'is-hierarchical'    => 'Turn on when records sit inside other records, e.g. lines inside a department.',
            'allowed-parents'    => 'The types a record may sit under. Leave empty to allow only records of this same type.',
            'approval-required'  => 'When on, record changes wait in the approvals inbox unless made by an approver of this type.',
            'completeness-scope' => 'Which fields count towards each record\'s completeness score.',
            'required-systems'   => 'Records missing an active ID from any of these systems get a quality issue.',
        ],

        'fields' => [
            'code'                  => 'Short unique ID of the field used in files and the API, e.g. line_speed. Fixed once saved.',
            'type'                  => 'The kind of value the field holds, e.g. Text, Date or Select. Fixed once saved.',
            'code-list'             => 'The list of allowed values this field offers, e.g. Units of Measure.',
            'reference-type'        => 'The entity type whose records this field points to, e.g. Department.',
            'group'                 => 'Section heading the field is shown under on the record form, e.g. Technical.',
            'position'              => 'Lower numbers are shown first on the record form.',
            'labels'                => 'The field name shown on the record form in each language.',
            'is_required'           => 'A record cannot be saved while this field is empty.',
            'is_unique'             => 'No two records of this type may hold the same value in this field.',
            'is_identifier'         => 'Builds the record code from this field when the code strategy is Identifier Fields.',
            'value_per_locale'      => 'Keep a separate value for each language, e.g. a translated description.',
            'is_effective_dated'    => 'Marks values that change over time. Stored as a note on the field.',
            'is_searchable'         => 'Lets users search and filter the records list by this field.',
            'multiple'              => 'For reference fields: allow links to more than one record.',
            'is_archived'           => 'Hides the field from forms. Values already stored are kept.',
            'validation-type'       => 'An extra check on the value, e.g. Email or URL.',
            'validation-regex'      => 'Pattern the value must match, e.g. ^[A-Z]{2}-\d{4}$ for codes like PL-0001.',
            'validation-min'        => 'Smallest allowed number, or shortest allowed text length.',
            'validation-max'        => 'Largest allowed number, or longest allowed text length.',
            'validation-max-length' => 'Longest allowed text, in characters.',
        ],

        'relation-types' => [
            'code'        => 'Short unique ID of the relation, e.g. feeds. It cannot be changed later.',
            'target-type' => 'The entity type of the records this relation points to, e.g. Equipment.',
            'cardinality' => 'Whether a record links to one record or to many records through this relation.',
            'labels'      => 'The relation name shown on record pages in each language, e.g. Feeds.',
        ],

        'code-lists' => [
            'code'         => 'Short unique ID of the list used in files and fields, e.g. units. It cannot be changed later.',
            'name'         => 'The list name people see, e.g. Units of Measure.',
            'description'  => 'Optional note explaining what the list is for.',
            'owner'        => 'Person or team responsible for the list, e.g. Quality team.',
            'is-versioned' => 'Shown as a label in the code list grid. Codes are always edited in a draft and published.',
        ],

        'codes' => [
            'code'           => 'The value stored in records, e.g. KG. Letters, digits, dots, dashes and underscores.',
            'parent-code'    => 'Optional code of this list that this code sits under, e.g. METRIC.',
            'sort'           => 'Lower numbers are listed first in pickers.',
            'is-active'      => 'Inactive codes are no longer offered or accepted for new values.',
            'effective-from' => 'First day the code can be used. Leave empty to use it right away.',
            'effective-to'   => 'The code stops being offered on this day. Leave empty to keep it open.',
            'labels'         => 'The text people see for this code in each language, e.g. Kilogram.',
        ],

        'aliases' => [
            'scope-type'            => 'Code List applies the alias to one list; Global applies it to every list.',
            'code-list'             => 'The code list whose terms this alias translates.',
            'field'                 => 'The field whose values this alias belongs to.',
            'term'                  => 'The value as a source system sends it, e.g. Kilo. Case, accents and spacing are ignored.',
            'target'                => 'The code the term should become, e.g. KG.',
            'locale'                => 'Language the term is written in, for reference. It does not limit lookups.',
            'source-system'         => 'Limit the alias to terms from one system. Leave empty to use it for every system.',
            'resolve-code-list'     => 'The code list to look the term up in.',
            'resolve-source-system' => 'Check as if the term came from this system. Leave empty for any system.',
            'resolve-term'          => 'Type a value as a source system would send it, e.g. kilo.',
        ],

        'icons' => [
            'code' => 'Short unique ID of the icon, e.g. conveyor. It cannot be changed later.',
            'name' => 'Name shown when picking an icon for an entity type, e.g. Conveyor.',
        ],

        'matching' => [
            'system'       => 'The system the row comes from, e.g. SAP ERP.',
            'entity-type'  => 'What kind of record the row describes, e.g. Equipment.',
            'external-id'  => 'The ID the source system uses for this item, e.g. EQ-10045. Matched exactly, including case.',
            'code'         => 'The code the system sends, if any. Used to suggest existing records.',
            'label'        => 'The name the system sends, if any. Used to suggest existing records.',
            'create-code'  => 'Code of the new record, prefilled from the incoming row.',
            'create-label' => 'Label of the new record, prefilled from the incoming row.',
        ],

        'crosswalk' => [
            'system'      => 'The system whose ID you have, e.g. SAP ERP.',
            'entity-type' => 'What kind of record the ID belongs to, e.g. Equipment.',
            'external-id' => 'The ID exactly as that system uses it, e.g. EQ-10045.',
        ],

        'records' => [
            'code'             => 'Unique code of this record within its type, e.g. LINE-01. It cannot be changed later.',
            'label'            => 'The name people see for this record, e.g. Filling Line 1.',
            'status'           => 'Draft while you are still preparing the record; set Active once it is ready to use.',
            'parent'           => 'The record this one sits under, e.g. the department of a line. Choose No parent (root) for the top level.',
            'xref-system'      => 'The system that knows this record under its own ID.',
            'xref-external-id' => 'The ID that system uses for this record, e.g. 10045.',
            'merge-duplicate'  => 'The record that was created twice. It is folded into this one and marked as merged.',
        ],

        'approvals' => [
            'comment' => 'Saved with your decision and shown in the request history. Required when you reject.',
        ],
    ],

    'platform-module' => [
        'name' => 'Master Data (MDM)',
    ],

    'access' => [
        'menu' => 'Data Access',
        'acl'  => [
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

    'hierarchies' => [
        'tab'            => 'Hierarchies',
        'title'          => 'Hierarchies',
        'info'           => 'Arrange records of this type in more than one tree. The primary hierarchy follows each record\'s parent; alternate hierarchies, such as a sales or reporting structure, are kept separately.',
        'create-btn'     => 'Add Hierarchy',
        'primary-label'  => 'Primary',
        'create-success' => 'Hierarchy added.',
        'update-success' => 'Hierarchy updated.',
        'delete-success' => 'Hierarchy deleted.',

        'create' => [
            'title' => 'Add Hierarchy',
        ],

        'edit' => [
            'title' => 'Edit Hierarchy',
        ],

        'form' => [
            'code'      => 'Code',
            'code-info' => 'Lowercase letters, digits and underscores, starting with a letter. It cannot change later.',
            'labels'    => 'Labels',
            'label'     => 'Label',
            'save-btn'  => 'Save Hierarchy',
        ],

        'datagrid' => [
            'code'   => 'Code',
            'label'  => 'Label',
            'kind'   => 'Kind',
            'edit'   => 'Edit',
            'delete' => 'Delete',
        ],

        'kinds' => [
            'primary'   => 'Primary',
            'alternate' => 'Alternate',
        ],

        'validation' => [
            'primary'           => 'The primary hierarchy follows each record\'s parent. Change the parent on the record instead.',
            'primary-delete'    => 'The primary hierarchy cannot be deleted.',
            'wrong-type'        => 'Only records of this hierarchy\'s entity type can be placed in it.',
            'subtree-too-large' => 'This record has more than :count records below it in this hierarchy, too many to move at once.',
            'code-taken'        => 'Another hierarchy of this entity type already uses this code.',
            'too-many'          => 'An entity type can have at most :count hierarchies.',
        ],

        'tree' => [
            'open'       => 'Tree View',
            'title'      => ':type Tree',
            'switch'     => 'Switch hierarchy',
            'empty'      => 'No hierarchy yet',
            'empty-info' => 'Make the entity type hierarchical or add an alternate hierarchy to see its records as a tree.',
            'loading'    => 'Loading…',
            'no-nodes'   => 'No records are placed in this hierarchy yet.',
            'expand'     => 'Expand',
            'collapse'   => 'Collapse',
            'limited'    => 'Showing the first :count records at this level.',
        ],

        'record' => [
            'tab'                    => 'Hierarchies',
            'title'                  => 'Alternate Hierarchies',
            'info'                   => 'Where this record sits in the alternate hierarchies of its entity type. The primary hierarchy is shown above.',
            'empty'                  => 'No alternate hierarchies',
            'empty-info'             => 'Add an alternate hierarchy on the entity type to place this record in it.',
            'hierarchy'              => 'Hierarchy',
            'path'                   => 'Path',
            'actions'                => 'Actions',
            'not-placed'             => 'Not placed',
            'place'                  => 'Place',
            'place-title'            => 'Place in :hierarchy',
            'parent-info'            => 'Choose a record of the same type, or no parent to put this record at the top. A parent not yet in the hierarchy is placed at the top first.',
            'remove'                 => 'Remove',
            'remove-confirm-title'   => 'Remove from hierarchy?',
            'remove-confirm-message' => 'The records below it move up one level.',
            'placed'                 => 'Record placed in :hierarchy.',
            'removed'                => 'Record removed from :hierarchy.',
        ],

        'api' => [
            'not-found' => 'Hierarchy not found.',
        ],
    ],
];
