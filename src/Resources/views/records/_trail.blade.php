<nav
    aria-label="{{ $label }}"
    class="flex flex-wrap items-center gap-1.5 text-sm {{ $class ?? '' }}"
>
    @foreach ($items as $item)
        @if ($item['is_current'])
            <span
                class="font-semibold text-gray-800 dark:text-white"
                aria-current="page"
            >
                {{ $item['label'] ?: $item['code'] }}
            </span>
        @else
            <a
                href="{{ route('admin.masterdata.records.edit', $item['uuid']) }}"
                class="text-primary-600 hover:text-primary-700 dark:text-primary-400"
            >
                {{ $item['label'] ?: $item['code'] }}
            </a>
        @endif

        @if (($withTypes ?? false) && $item['type'])
            <x-admin::badge variant="info">{{ $item['type'] }}</x-admin::badge>
        @endif

        @unless ($loop->last)
            <span
                class="text-gray-400 dark:text-gray-500"
                aria-hidden="true"
            >
                @lang('masterdata::app.hierarchy.trail-separator')
            </span>
        @endunless
    @endforeach

    @if (count($items) === 1)
        <span class="text-gray-600 dark:text-gray-300">
            {{ $rootLabel }}
        </span>
    @endif
</nav>
