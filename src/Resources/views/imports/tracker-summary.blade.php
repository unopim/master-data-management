<div class="grid grid-cols-2 gap-px border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mt-4">
    <div class="p-3 bg-white dark:bg-cherry-900">
        <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('masterdata::app.imports.summary.held')</p>
        <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(importResource.summary?.held || 0).toLocaleString() }}</p>
    </div>

    <div class="p-3 bg-white dark:bg-cherry-900 border-l border-gray-200 dark:border-gray-700">
        <p class="text-[10px] font-semibold tracking-wider text-gray-400 dark:text-gray-500 uppercase">@lang('masterdata::app.imports.summary.skipped')</p>
        <p class="text-xl font-bold text-gray-800 dark:text-gray-200 mt-1">@{{ Number(importResource.summary?.skipped || 0).toLocaleString() }}</p>
    </div>
</div>

<p class="text-xs text-gray-600 dark:text-gray-400 mt-2">@lang('masterdata::app.imports.summary.info')</p>
