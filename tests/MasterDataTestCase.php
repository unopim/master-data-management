<?php

namespace Webkul\MasterDataManagement\Tests;

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Tests\Concerns\InteractsWithPlatformModules;
use Webkul\MasterDataManagement\Models\Alias;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Field;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Models\SourceSystem;
use Webkul\MasterDataManagement\Models\Xref;
use Webkul\User\Tests\Concerns\UserAssertions;

class MasterDataTestCase extends TestCase
{
    use InteractsWithPlatformModules;
    use UserAssertions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Channel::query()->exists()) {
            if (! Category::whereIsRoot()->exists()) {
                $rootCategory = Category::create([
                    'code' => 'root',
                ]);

                $rootCategory->additional_data = [
                    'locale_specific' => [
                        config('app.locale') => [
                            'name' => 'Root',
                        ],
                    ],
                ];

                $rootCategory->save();
            }

            Channel::factory()->create();
        }

        $channel = Channel::first();
        config()->set('app.channel', $channel->code);
        core()->setDefaultChannel($channel);

        $this->setPlatformModules(['pim', 'mdm']);
    }

    protected function uniqueCode(string $prefix): string
    {
        return $prefix.'_'.strtolower(Str::random(8));
    }

    protected function resolveUrl(array $query): string
    {
        return route('admin.api.masterdata.xref.resolve', $query);
    }

    protected function ajaxGrid(string $url): TestResponse
    {
        return $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->getJson($url);
    }

    /**
     * @return array{int, int, int, int} Entity type, source system, record and crosswalk totals of the manufacturing demo.
     */
    protected function demoCounts(): array
    {
        $types = EntityType::query()->whereIn('code', ['site', 'department', 'area', 'line', 'equipment', 'platform'])->pluck('id');

        return [
            $types->count(),
            SourceSystem::query()->whereIn('code', ['erp', 'mes', 'cmms'])->count(),
            Record::query()->whereIn('entity_type_id', $types)->count(),
            Xref::query()->whereIn('entity_type_id', $types)->count(),
            CodeList::query()->whereIn('code', ['line_type', 'equipment_status'])->count(),
            Field::query()->whereIn('entity_type_id', $types)->where('is_archived', false)->count(),
            Alias::query()->count(),
        ];
    }
}
