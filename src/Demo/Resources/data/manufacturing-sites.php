<?php

return [
    'personas' => [
        'owner' => [
            'role'        => 'Site Data Owner',
            'description' => 'Accountable for site, line and equipment master data; approves or rejects the stewards\' changes.',
            'name'        => 'Site Data Owner',
            'email'       => 'site.owner@example.com',
            'actions'     => ['view', 'create', 'edit', 'delete', 'merge', 'approve'],
            'permissions' => [
                'dashboard', 'masterdata', 'masterdata.entity_types', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.create',
                'masterdata.records.edit', 'masterdata.records.delete', 'masterdata.records.merge', 'masterdata.records.approve', 'masterdata.source_systems',
                'masterdata.code_lists', 'masterdata.code_lists.create', 'masterdata.code_lists.edit', 'masterdata.matching', 'masterdata.matching.create',
                'masterdata.matching.edit', 'masterdata.crosswalk', 'masterdata.quality', 'masterdata.quality.scan', 'masterdata.quality.resolve', 'masterdata.approvals',
                'data_transfer', 'data_transfer.job_tracker', 'data_transfer.imports', 'data_transfer.imports.execute', 'data_transfer.export', 'data_transfer.export.execute',
            ],
        ],
        'steward' => [
            'role'        => 'Site Data Steward',
            'description' => 'Maintains site, line and equipment master data; changes need data owner approval.',
            'name'        => 'Site Data Steward',
            'email'       => 'site.steward@example.com',
            'actions'     => ['view', 'create', 'edit', 'delete'],
            'permissions' => [
                'dashboard', 'masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.records.create', 'masterdata.records.edit',
                'masterdata.code_lists', 'masterdata.matching', 'masterdata.matching.create', 'masterdata.matching.edit', 'masterdata.crosswalk',
                'masterdata.quality', 'masterdata.approvals',
            ],
        ],
        'reference_data' => [
            'role'        => 'Reference Data Manager',
            'description' => 'Owns the code lists, approved values, aliases and abbreviations, and the source system register.',
            'name'        => 'Reference Data Manager',
            'email'       => 'reference.data@example.com',
            'actions'     => ['view'],
            'permissions' => [
                'dashboard', 'masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.code_lists', 'masterdata.code_lists.create',
                'masterdata.code_lists.edit', 'masterdata.source_systems', 'masterdata.source_systems.create', 'masterdata.source_systems.edit',
                'masterdata.crosswalk',
            ],
        ],
        'quality' => [
            'role'        => 'Data Quality Analyst',
            'description' => 'Runs the quality scans and works the completeness, validity, cross-system and duplicate issues.',
            'name'        => 'Data Quality Analyst',
            'email'       => 'data.quality@example.com',
            'actions'     => ['view'],
            'permissions' => [
                'dashboard', 'masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.crosswalk', 'masterdata.quality',
                'masterdata.quality.scan', 'masterdata.quality.resolve',
            ],
        ],
        'publisher' => [
            'role'        => 'Analytics Publisher',
            'description' => 'Publishes the governed site data to the data lake and Fabric analytical layer; read only on records.',
            'name'        => 'Analytics Publisher',
            'email'       => 'analytics.publisher@example.com',
            'actions'     => ['view'],
            'permissions' => [
                'dashboard', 'masterdata', 'masterdata.records', 'masterdata.records.view', 'masterdata.crosswalk',
                'data_transfer', 'data_transfer.job_tracker', 'data_transfer.export', 'data_transfer.export.execute',
            ],
        ],
    ],

    'source_systems' => [
        ['code' => 'sap_s4', 'name' => 'SAP S/4HANA (MM / PM)', 'type' => 'erp', 'priority' => 10, 'contact_owner' => 'ERP master data team'],
        ['code' => 'mes', 'name' => 'Plant MES', 'type' => 'mes', 'priority' => 20, 'contact_owner' => 'Manufacturing IT'],
        ['code' => 'maximo', 'name' => 'Maximo CMMS', 'type' => 'cmms', 'priority' => 30, 'contact_owner' => 'Reliability engineering'],
        ['code' => 'pi_af', 'name' => 'PI Asset Framework (Historian)', 'type' => 'other', 'priority' => 40, 'contact_owner' => 'Process data engineering'],
        ['code' => 'ehs', 'name' => 'EHS Compliance', 'type' => 'other', 'priority' => 50, 'contact_owner' => 'EHS office'],
    ],

    'code_lists' => [
        'site_type' => [
            'name'  => 'Site type',
            'codes' => ['manufacturing_plant' => 'Manufacturing plant', 'distribution_center' => 'Distribution center', 'rnd_center' => 'R&D center', 'contract_manufacturer' => 'Contract manufacturer'],
        ],
        'region' => [
            'name'  => 'Region',
            'codes' => ['north_america' => 'North America', 'latin_america' => 'Latin America', 'europe' => 'Europe', 'imea' => 'India, Middle East & Africa', 'asia_pacific' => 'Asia Pacific', 'greater_china' => 'Greater China'],
        ],
        'country' => [
            'name'  => 'Country (ISO 3166)',
            'codes' => ['US' => 'United States', 'DE' => 'Germany', 'PL' => 'Poland', 'IN' => 'India', 'MX' => 'Mexico', 'CN' => 'China', 'BE' => 'Belgium'],
        ],
        'department_function' => [
            'name'  => 'Department function',
            'codes' => ['making' => 'Making', 'packing' => 'Packing', 'utilities' => 'Utilities', 'warehouse' => 'Warehouse', 'quality' => 'Quality assurance', 'maintenance' => 'Maintenance'],
        ],
        'line_type' => [
            'name'  => 'Line type',
            'codes' => ['making' => 'Making (batch)', 'filling' => 'Filling', 'packing' => 'Packing', 'converting' => 'Converting'],
            'draft' => ['palletizing' => 'Palletizing'],
        ],
        'area_classification' => [
            'name'  => 'Area classification',
            'codes' => ['process_wet' => 'Process - wet', 'process_dry' => 'Process - dry', 'packaging' => 'Packaging hall', 'hazardous_atex' => 'Hazardous (ATEX)', 'storage' => 'Storage'],
        ],
        'equipment_class' => [
            'name'  => 'Equipment class',
            'codes' => ['dosing_skid' => 'Dosing skid', 'pump' => 'Pump', 'mixer' => 'Mixer', 'homogenizer' => 'Homogenizer', 'filler' => 'Filler', 'checkweigher' => 'Checkweigher', 'capper' => 'Capper', 'labeler' => 'Labeler', 'case_packer' => 'Case packer', 'carton_erector' => 'Carton erector', 'palletizer' => 'Palletizer', 'stretch_wrapper' => 'Stretch wrapper'],
        ],
        'operating_status' => [
            'name'  => 'Operating status',
            'codes' => ['running' => 'Running', 'idle' => 'Idle', 'maintenance' => 'Under maintenance', 'decommissioned' => 'Decommissioned'],
        ],
        'criticality' => [
            'name'  => 'Asset criticality',
            'codes' => ['A' => 'A - safety / quality critical', 'B' => 'B - production critical', 'C' => 'C - non critical'],
        ],
    ],

    'aliases' => [
        ['list' => 'department_function', 'term' => 'MFG', 'target' => 'making'],
        ['list' => 'department_function', 'term' => 'Production', 'target' => 'making'],
        ['list' => 'department_function', 'term' => 'PKG', 'target' => 'packing'],
        ['list' => 'department_function', 'term' => 'Packaging', 'target' => 'packing'],
        ['list' => 'department_function', 'term' => 'QA', 'target' => 'quality'],
        ['list' => 'department_function', 'term' => 'QC', 'target' => 'quality'],
        ['list' => 'department_function', 'term' => 'Whse', 'target' => 'warehouse'],
        ['list' => 'department_function', 'term' => 'UTIL', 'target' => 'utilities'],
        ['list' => 'department_function', 'term' => 'Maint', 'target' => 'maintenance'],
        ['list' => 'line_type', 'term' => 'FL', 'target' => 'filling'],
        ['list' => 'line_type', 'term' => 'Fill Line', 'target' => 'filling'],
        ['list' => 'line_type', 'term' => 'PK', 'target' => 'packing', 'system' => 'mes'],
        ['list' => 'line_type', 'term' => 'Batch', 'target' => 'making'],
        ['list' => 'equipment_class', 'term' => 'CW', 'target' => 'checkweigher'],
        ['list' => 'equipment_class', 'term' => 'Check Weigher', 'target' => 'checkweigher'],
        ['list' => 'equipment_class', 'term' => 'Palletiser', 'target' => 'palletizer'],
        ['list' => 'equipment_class', 'term' => 'Lbl', 'target' => 'labeler'],
        ['list' => 'equipment_class', 'term' => 'Labeller', 'target' => 'labeler'],
        ['list' => 'equipment_class', 'term' => 'FILLR', 'target' => 'filler', 'system' => 'maximo'],
        ['list' => 'operating_status', 'term' => 'RUN', 'target' => 'running', 'system' => 'mes'],
        ['list' => 'operating_status', 'term' => 'DOWN', 'target' => 'maintenance', 'system' => 'mes'],
        ['list' => 'operating_status', 'term' => 'DECOM', 'target' => 'decommissioned', 'system' => 'maximo'],
        ['list' => 'country', 'term' => 'USA', 'target' => 'US'],
        ['list' => 'country', 'term' => 'United States of America', 'target' => 'US'],
        ['list' => 'country', 'term' => 'Deutschland', 'target' => 'DE'],
        ['list' => 'country', 'term' => 'Bharat', 'target' => 'IN'],
        ['list' => 'region', 'term' => 'NA', 'target' => 'north_america'],
        ['list' => 'region', 'term' => 'EU', 'target' => 'europe'],
        ['list' => 'region', 'term' => 'LATAM', 'target' => 'latin_america'],
        ['list' => 'region', 'term' => 'GC', 'target' => 'greater_china'],
        ['list' => 'region', 'term' => 'APAC', 'target' => 'asia_pacific'],
    ],

    'entity_types' => [
        'site' => [
            'name'             => 'Site',
            'description'      => 'A factory or plant at one address.',
            'sort_order'       => 10,
            'icon'             => 'icon-channel',
            'parents'          => [],
            'governed'         => true,
            'required_systems' => ['sap_s4', 'ehs'],
            'fields'           => [
                ['code' => 'name', 'label' => 'Site name', 'type' => 'text', 'is_required' => true, 'is_searchable' => true],
                ['code' => 'site_type', 'label' => 'Site type', 'type' => 'select', 'list' => 'site_type', 'is_required' => true],
                ['code' => 'region', 'label' => 'Region', 'type' => 'select', 'list' => 'region', 'is_required' => true],
                ['code' => 'country', 'label' => 'Country', 'type' => 'select', 'list' => 'country', 'is_required' => true],
                ['code' => 'city', 'label' => 'City', 'type' => 'text', 'is_searchable' => true],
                ['code' => 'timezone', 'label' => 'Time zone', 'type' => 'text'],
                ['code' => 'gln', 'label' => 'GLN (GS1 location number)', 'type' => 'text', 'is_unique' => true, 'validation' => ['regex' => '/^\d{13}$/']],
                ['code' => 'site_manager_email', 'label' => 'Site manager e-mail', 'type' => 'email'],
                ['code' => 'operational_since', 'label' => 'Operational since', 'type' => 'date'],
            ],
        ],
        'department' => [
            'name'        => 'Department',
            'description' => 'A section of a plant with its own team and budget, like Packing or Quality.',
            'sort_order'  => 20,
            'icon'        => 'icon-folder',
            'parents'     => ['site'],
            'fields'      => [
                ['code' => 'name', 'label' => 'Department name', 'type' => 'text', 'is_required' => true, 'is_searchable' => true],
                ['code' => 'function', 'label' => 'Function', 'type' => 'select', 'list' => 'department_function', 'is_required' => true],
                ['code' => 'cost_center', 'label' => 'Cost center', 'type' => 'text', 'validation' => ['regex' => '/^CC-\d{5}$/']],
            ],
        ],
        'platform' => [
            'name'        => 'Platform',
            'description' => 'A technology group of lines across all plants, like Liquid Laundry.',
            'sort_order'  => 30,
            'icon'        => 'icon-catalog',
            'parents'     => [],
            'fields'      => [
                ['code' => 'name', 'label' => 'Platform name', 'type' => 'text', 'is_required' => true, 'is_searchable' => true],
                ['code' => 'category_group', 'label' => 'Category group', 'type' => 'text'],
                ['code' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ],
        ],
        'line' => [
            'name'             => 'Line',
            'description'      => 'A chain of machines that makes, fills or packs product.',
            'sort_order'       => 40,
            'icon'             => 'icon-processing',
            'parents'          => ['department'],
            'governed'         => true,
            'required_systems' => ['sap_s4', 'mes'],
            'fields'           => [
                ['code' => 'name', 'label' => 'Line name', 'type' => 'text', 'is_required' => true, 'is_searchable' => true],
                ['code' => 'line_type', 'label' => 'Line type', 'type' => 'select', 'list' => 'line_type', 'is_required' => true],
                ['code' => 'platform', 'label' => 'Platform', 'type' => 'reference', 'reference' => 'platform'],
                ['code' => 'rated_speed_ppm', 'label' => 'Rated speed (units / min)', 'type' => 'integer', 'validation' => ['min' => 1, 'max' => 2000]],
                ['code' => 'shifts_per_day', 'label' => 'Shifts per day', 'type' => 'integer', 'validation' => ['min' => 1, 'max' => 4]],
                ['code' => 'operating_status', 'label' => 'Operating status', 'type' => 'select', 'list' => 'operating_status'],
                ['code' => 'commissioned_on', 'label' => 'Commissioned on', 'type' => 'date'],
            ],
        ],
        'area' => [
            'name'        => 'Area',
            'description' => 'A room or zone along a line, like the Filling Room.',
            'sort_order'  => 50,
            'icon'        => 'icon-dashboard',
            'parents'     => ['line'],
            'fields'      => [
                ['code' => 'name', 'label' => 'Area name', 'type' => 'text', 'is_required' => true, 'is_searchable' => true],
                ['code' => 'classification', 'label' => 'Classification', 'type' => 'select', 'list' => 'area_classification'],
                ['code' => 'floor_space_m2', 'label' => 'Floor space (m²)', 'type' => 'decimal'],
            ],
        ],
        'equipment' => [
            'name'             => 'Equipment',
            'description'      => 'One machine on a line, like a filler or checkweigher.',
            'sort_order'       => 60,
            'icon'             => 'icon-setting',
            'parents'          => ['area'],
            'governed'         => true,
            'required_systems' => ['sap_s4', 'maximo'],
            'fields'           => [
                ['code' => 'name', 'label' => 'Equipment name', 'type' => 'text', 'is_required' => true, 'is_searchable' => true],
                ['code' => 'equipment_class', 'label' => 'Equipment class', 'type' => 'select', 'list' => 'equipment_class', 'is_required' => true],
                ['code' => 'manufacturer', 'label' => 'Manufacturer', 'type' => 'text', 'is_searchable' => true],
                ['code' => 'model', 'label' => 'Model', 'type' => 'text'],
                ['code' => 'serial_number', 'label' => 'Serial number', 'type' => 'text', 'is_unique' => true],
                ['code' => 'criticality', 'label' => 'Criticality', 'type' => 'select', 'list' => 'criticality', 'required_after_load' => true],
                ['code' => 'operating_status', 'label' => 'Operating status', 'type' => 'select', 'list' => 'operating_status'],
                ['code' => 'install_date', 'label' => 'Install date', 'type' => 'date'],
            ],
        ],
    ],

    'platforms' => [
        ['code' => 'PLAT-LIQ', 'name' => 'Liquid Laundry', 'category_group' => 'Fabric & Home Care', 'description' => 'Heavy-duty liquid detergents, batch made and filled into bottles and jugs.', 'xrefs' => ['sap_s4' => 'PLT-0001', 'mes' => 'LIQ']],
        ['code' => 'PLAT-UDO', 'name' => 'Unit Dose Laundry', 'category_group' => 'Fabric & Home Care', 'description' => 'Water-soluble film unit doses, made on continuous lines and packed into tubs.', 'xrefs' => ['sap_s4' => 'PLT-0002', 'mes' => 'PODS']],
        ['code' => 'PLAT-PWD', 'name' => 'Powder Laundry', 'category_group' => 'Fabric & Home Care', 'description' => 'Spray-dried and agglomerated laundry powders packed into cartons and bags.', 'xrefs' => ['sap_s4' => 'PLT-0003', 'mes' => 'PWD']],
        ['code' => 'PLAT-DISH', 'name' => 'Hand Dish Liquids', 'category_group' => 'Fabric & Home Care', 'description' => 'Hand dishwashing liquids filled into squeeze bottles.', 'xrefs' => ['sap_s4' => 'PLT-0004', 'mes' => 'DISH']],
        ['code' => 'PLAT-HAIR', 'name' => 'Hair Care Liquids', 'category_group' => 'Beauty', 'description' => 'Shampoos and conditioners, batch made and filled into bottles with flip caps.', 'xrefs' => ['sap_s4' => 'PLT-0005', 'mes' => 'HAIR']],
        ['code' => 'PLAT-ORAL', 'name' => 'Oral Care Paste', 'category_group' => 'Health Care', 'description' => 'Toothpaste, vacuum mixed and filled into laminate tubes.', 'xrefs' => ['sap_s4' => 'PLT-0006', 'mes' => 'ORAL']],
    ],

    'sites' => [
        [
            'code'              => 'LKV', 'name' => 'Lakeview Plant', 'site_type' => 'manufacturing_plant', 'region' => 'north_america', 'country' => 'US',
            'city'              => 'Lakeview, OH', 'timezone' => 'America/New_York', 'gln' => '0614141000012', 'site_manager_email' => 'lakeview.site.manager@example.com',
            'operational_since' => '1987-05-04', 'sap' => '1010', 'mes' => 'LKV', 'maximo' => 'LKVOH', 'pi' => 'PIAF-US', 'ehs' => 'US-OH-0042',
            'platform'          => 'PLAT-LIQ',
        ],
        [
            'code'              => 'RHN', 'name' => 'Rheinau Plant', 'site_type' => 'manufacturing_plant', 'region' => 'europe', 'country' => 'DE',
            'city'              => 'Rheinau', 'timezone' => 'Europe/Berlin', 'gln' => '4012345000016', 'site_manager_email' => 'rheinau.site.manager@example.com',
            'operational_since' => '1994-09-12', 'sap' => '2040', 'mes' => 'RHN', 'maximo' => 'RHNDE', 'pi' => 'PIAF-EU', 'ehs' => 'DE-BW-0117',
            'platform'          => 'PLAT-LIQ',
        ],
        [
            'code'              => 'PNE', 'name' => 'Pune Plant', 'site_type' => 'manufacturing_plant', 'region' => 'imea', 'country' => 'IN',
            'city'              => 'Pune', 'timezone' => 'Asia/Kolkata', 'gln' => '8901234000014', 'site_manager_email' => 'pune.site.manager@example.com',
            'operational_since' => '2009-02-02', 'sap' => '3110', 'mes' => 'PNE', 'maximo' => 'PNEIN', 'pi' => 'PIAF-AP', 'ehs' => 'IN-MH-0203',
            'platform'          => 'PLAT-HAIR',
        ],
    ],

    'departments' => [
        ['suffix' => 'MAK', 'function' => 'making', 'name' => 'Making', 'sap' => 'MK', 'mes' => 'MAKING', 'maximo' => 'MFG'],
        ['suffix' => 'PCK', 'function' => 'packing', 'name' => 'Packing', 'sap' => 'PK', 'mes' => 'PACK', 'maximo' => 'PKG'],
        ['suffix' => 'UTL', 'function' => 'utilities', 'name' => 'Utilities', 'sap' => 'UT', 'mes' => 'UTIL', 'maximo' => 'UTL'],
        ['suffix' => 'WHS', 'function' => 'warehouse', 'name' => 'Warehouse', 'sap' => 'WH', 'mes' => 'WHSE', 'maximo' => 'WHS'],
        ['suffix' => 'QA', 'function' => 'quality', 'name' => 'Quality Assurance', 'sap' => 'QA', 'mes' => 'QUALITY', 'maximo' => 'QA'],
    ],

    'lines' => [
        [
            'number' => 1, 'department' => 'MAK', 'line_type' => 'making', 'label' => 'Making', 'speed' => 40, 'shifts' => 3,
            'areas'  => [
                ['name' => 'Raw Material Dosing', 'classification' => 'hazardous_atex', 'space' => 420.0, 'equipment' => [['Dosing Skid', 'dosing_skid', 'Aquila Process', 'DS-600'], ['Transfer Pump', 'pump', 'Aquila Process', 'TP-80']]],
                ['name' => 'Batch Mixing', 'classification' => 'process_wet', 'space' => 610.5, 'equipment' => [['Batch Mixer', 'mixer', 'Norden Mix', 'BM-12000'], ['Inline Homogenizer', 'homogenizer', 'Norden Mix', 'HX-40']]],
            ],
        ],
        [
            'number' => 2, 'department' => 'PCK', 'line_type' => 'filling', 'label' => 'Filling', 'speed' => 240, 'shifts' => 3,
            'areas'  => [
                ['name' => 'Filling Room', 'classification' => 'process_wet', 'space' => 380.0, 'equipment' => [['Rotary Filler', 'filler', 'Vexa Packaging', 'RF-36'], ['Checkweigher', 'checkweigher', 'Metrix', 'CW-900']]],
                ['name' => 'Capping and Labelling', 'classification' => 'packaging', 'space' => 290.0, 'equipment' => [['Capper', 'capper', 'Vexa Packaging', 'CP-24'], ['Labeler', 'labeler', 'Etika Systems', 'LB-300']]],
            ],
        ],
        [
            'number' => 3, 'department' => 'PCK', 'line_type' => 'packing', 'label' => 'Packing', 'speed' => 60, 'shifts' => 2,
            'areas'  => [
                ['name' => 'Case Packing', 'classification' => 'packaging', 'space' => 350.0, 'equipment' => [['Case Packer', 'case_packer', 'Boxline', 'CPK-20'], ['Carton Erector', 'carton_erector', 'Boxline', 'CE-15']]],
                ['name' => 'Palletizing and Wrapping', 'classification' => 'packaging', 'space' => 520.0, 'equipment' => [['Robot Palletizer', 'palletizer', 'Stakr Robotics', 'RP-4'], ['Stretch Wrapper', 'stretch_wrapper', 'Stakr Robotics', 'SW-2']]],
            ],
        ],
    ],

    'gaps' => [
        'missing_maximo'      => ['RHN-L03-A2-E2', 'PNE-L02-A1-E1'],
        'missing_criticality' => ['LKV-L01-A1-E2', 'RHN-L02-A2-E2', 'PNE-L03-A1-E2'],
        'missing_mes_line'    => ['PNE-L03'],
        'duplicates'          => [
            ['code' => 'LKV-L02-A1-E3', 'parent' => 'LKV-L02-A1', 'name' => 'Lakeview L2 Check Weigher', 'class' => 'checkweigher', 'maximo' => 'A-0101207'],
        ],
    ],

    'match_inbox' => [
        ['system' => 'pi_af', 'type' => 'equipment', 'external_id' => '\\\\PIAF-US\\Lakeview\\Packing\\Line 2\\CW-01', 'code' => 'LKV-L2-CW01', 'label' => 'Lakeview L2 Check-weigher'],
        ['system' => 'mes', 'type' => 'equipment', 'external_id' => 'RHN_L1_MIX01', 'code' => 'RHN-L1-MIX01', 'label' => 'Rheinau L1 Batch Mixer'],
        ['system' => 'ehs', 'type' => 'line', 'external_id' => 'US-OH-0042-L3', 'code' => 'LKV-PACK-3', 'label' => 'Lakeview Line 3 Packing'],
        ['system' => 'maximo', 'type' => 'equipment', 'external_id' => 'A-0309981', 'code' => 'PNE-L2-FILLR2', 'label' => 'Pune L2 Rotary Filler No 2'],
        ['system' => 'ehs', 'type' => 'site', 'external_id' => 'PL-MZ-0310', 'code' => 'WSL', 'label' => 'Wislana Plant'],
    ],

    'export_jobs' => [
        ['code' => 'mdm_sites_to_fabric', 'entity_type' => 'site', 'format' => 'Jsonl'],
        ['code' => 'mdm_lines_to_fabric', 'entity_type' => 'line', 'format' => 'Jsonl'],
        ['code' => 'mdm_equipment_to_fabric', 'entity_type' => 'equipment', 'format' => 'Jsonl'],
        ['code' => 'mdm_equipment_crosswalk_csv', 'entity_type' => 'equipment', 'format' => 'Csv'],
        ['code' => 'mdm_platforms_to_fabric', 'entity_type' => 'platform', 'format' => 'Jsonl'],
        ['code' => 'mdm_departments_to_fabric', 'entity_type' => 'department', 'format' => 'Jsonl'],
        ['code' => 'mdm_areas_to_fabric', 'entity_type' => 'area', 'format' => 'Jsonl'],
    ],

    'import_jobs' => [
        [
            'code'     => 'mdm_maximo_equipment_import',
            'job_type' => 'masterdata_records',
            'filters'  => ['entity_type' => 'equipment', 'source_system' => 'maximo'],
        ],
        [
            'code'     => 'mdm_sap_lines_import',
            'job_type' => 'masterdata_records',
            'sample'   => 'sap-lines.csv',
            'filters'  => [
                'entity_type'   => 'line',
                'source_system' => 'sap_s4',
                'mapping'       => "MDM Code=code\nSAP Line ID=external_id\nRated Speed=rated_speed_ppm\nShifts=shifts_per_day\nStatus=operating_status",
            ],
        ],
        [
            'code'     => 'mdm_maximo_crosswalk_import',
            'job_type' => 'masterdata_xrefs',
            'filters'  => ['entity_type' => 'equipment', 'source_system' => 'maximo'],
        ],
        [
            'code'     => 'mdm_equipment_class_import',
            'job_type' => 'masterdata_code_lists',
            'filters'  => ['code_list' => 'equipment_class'],
        ],
    ],
];
