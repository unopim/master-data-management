@php
    $referenceTabs = [
        [
            'key'   => 'code-lists',
            'url'   => route('admin.masterdata.code_lists.index'),
            'label' => 'masterdata::app.code-lists.tabs.code-lists',
        ],
    ];

    if (bouncer()->hasPermission('masterdata.code_lists.edit')) {
        $referenceTabs[] = [
            'key'   => 'aliases',
            'url'   => route('admin.masterdata.aliases.index'),
            'label' => 'masterdata::app.code-lists.tabs.aliases',
        ];
    }
@endphp

<x-admin::layouts.edit-tabs
    :items="$referenceTabs"
    :active="$activeReferenceTab"
/>
