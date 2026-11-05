<x-admin::layouts>
    <x-slot:title>
        @lang('masterdata::app.dashboard.title')
    </x-slot>

    <x-admin::layouts.page-header :title="trans('masterdata::app.dashboard.title')" />

    @if ($entityTypes->isEmpty())
        <div class="mt-3.5 p-4 bg-white dark:bg-cherry-900 rounded box-shadow">
            <x-admin::list.empty-state
                icon="icon-folder"
                :title="trans('masterdata::app.dashboard.empty')"
            />
        </div>
    @else
        <div class="mt-3.5 grid grid-cols-3 gap-4 max-xl:grid-cols-2 max-sm:grid-cols-1">
            @foreach ($entityTypes as $entityType)
                <x-admin::card
                    :url="route('admin.masterdata.records.index', $entityType->code)"
                    target="_self"
                    :icon="$entityType->icon ?: 'icon-dashboard'"
                    :icon-url="$entityType->libraryIcon?->url()"
                    :icon-mask="$entityType->libraryIcon?->isVector() ?? true"
                    :title="$entityType->name ?: $entityType->code"
                >
                    @if (filled($entityType->description))
                        <span class="block mb-2 line-clamp-2">{{ $entityType->description }}</span>
                    @endif

                    <span class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
                        <span class="font-semibold text-gray-600 dark:text-gray-300">{{ $placements[$entityType->id] ?? '' }}</span>

                        <span aria-hidden="true">&middot;</span>

                        <span>{{ trans_choice('masterdata::app.dashboard.record-count', $counts[$entityType->id] ?? 0) }}</span>
                    </span>
                </x-admin::card>
            @endforeach
        </div>
    @endif
</x-admin::layouts>
