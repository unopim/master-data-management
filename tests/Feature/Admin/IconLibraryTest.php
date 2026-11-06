<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Webkul\MasterDataManagement\Database\Seeders\IconLibrarySeeder;
use Webkul\MasterDataManagement\Http\Requests\EntityTypeRequest;
use Webkul\MasterDataManagement\Models\EntityType;
use Webkul\MasterDataManagement\Models\Icon;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;
use Webkul\MasterDataManagement\Services\EntityTypeFormOptions;
use Webkul\MasterDataManagement\Services\IconStore;

beforeEach(function () {
    Storage::fake();

    $this->loginAsAdmin();
});

function librarySvg(string $body = '<path d="M2 2h20v20H2z"/>'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'icon.svg',
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">'.$body.'</svg>',
    );
}

function createIcon(mixed $file = null, ?string $code = null): TestResponse
{
    return test()->post(route('admin.masterdata.icons.store'), [
        'code' => $code ?? test()->uniqueCode('icon'),
        'name' => 'Warehouse',
        'file' => [$file ?? librarySvg()],
    ], ['Accept' => 'application/json']);
}

function libraryIcon(): Icon
{
    createIcon()->assertOk();

    return Icon::query()->latest('id')->firstOrFail();
}

function iconType(array $attributes = []): EntityType
{
    return app(EntityTypeRepository::class)->create([
        'code'                                           => test()->uniqueCode('plant'),
        core()->getDefaultLocaleCodeFromDefaultChannel() => ['name' => 'Plant'],
        ...$attributes,
    ]);
}

it('adds an icon to the library', function () {
    $icon = libraryIcon();

    expect($icon->name)->toBe('Warehouse')
        ->and($icon->path)->toStartWith(IconStore::DIRECTORY.'/')->toEndWith('.svg');

    Storage::assertExists($icon->path);
});

it('accepts a png icon', function () {
    createIcon(UploadedFile::fake()->image('icon.png', 64, 64))->assertOk();

    expect(Icon::query()->latest('id')->value('path'))->toEndWith('.png');
});

it('strips scripts and event handlers from an uploaded svg', function () {
    createIcon(librarySvg('<script>alert(1)</script><path onload="alert(2)" d="M2 2h20v20H2z"/>'))->assertOk();

    expect(Storage::get(Icon::query()->latest('id')->value('path')))
        ->not->toContain('<script')
        ->not->toContain('onload')
        ->toContain('<path');
});

it('rejects an invalid icon upload', function (UploadedFile $file) {
    createIcon($file)->assertUnprocessable()->assertJsonValidationErrors('file.0');
})->with([
    'wrong type' => fn () => UploadedFile::fake()->create('icon.pdf', 10, 'application/pdf'),
    'too big'    => fn () => UploadedFile::fake()->image('icon.png', 64, 64)->size(IconStore::MAX_KILOBYTES + 1),
    'too small'  => fn () => UploadedFile::fake()->image('icon.png', 8, 8),
]);

it('requires a file and a unique code', function () {
    $icon = libraryIcon();

    $this->postJson(route('admin.masterdata.icons.store'), ['code' => $icon->code, 'name' => 'X'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'file']);
});

it('renames an icon and keeps its file', function () {
    $icon = libraryIcon();

    $this->putJson(route('admin.masterdata.icons.update', $icon->id), ['name' => 'Depot', 'file' => $icon->path])->assertOk();

    expect($icon->refresh()->name)->toBe('Depot');

    Storage::assertExists($icon->path);
});

it('replaces the file and deletes the old one', function () {
    $icon = libraryIcon();
    $old = $icon->path;

    $this->put(route('admin.masterdata.icons.update', $icon->id), [
        'name' => 'Depot',
        'file' => [UploadedFile::fake()->image('icon.png', 64, 64)],
    ], ['Accept' => 'application/json'])->assertOk();

    Storage::assertMissing($old);
    Storage::assertExists($icon->refresh()->path);
});

it('lets several entity types share one icon', function () {
    $icon = libraryIcon();

    $first = iconType();
    $second = iconType();

    foreach ([$first, $second] as $type) {
        $this->putJson(route('admin.masterdata.entity_types.update', $type->id), [
            'icon' => EntityTypeRequest::LIBRARY_ICON_PREFIX.$icon->id,
        ])->assertOk();
    }

    expect($first->refresh()->icon_id)->toBe($icon->id)
        ->and($first->icon)->toBeNull()
        ->and($second->refresh()->icon_id)->toBe($icon->id);
});

it('switches an entity type back to a font icon', function () {
    $type = iconType(['icon_id' => libraryIcon()->id]);

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), ['icon' => 'icon-folder'])->assertOk();

    expect($type->refresh()->icon_id)->toBeNull()
        ->and($type->icon)->toBe('icon-folder');
});

it('rejects an unknown library icon on an entity type', function () {
    $type = iconType();

    $this->putJson(route('admin.masterdata.entity_types.update', $type->id), [
        'icon' => EntityTypeRequest::LIBRARY_ICON_PREFIX.'999999',
    ])->assertUnprocessable()->assertJsonValidationErrors('icon_id');
});

it('refuses to delete an icon an entity type shows', function () {
    $icon = libraryIcon();
    iconType(['icon_id' => $icon->id]);

    $this->deleteJson(route('admin.masterdata.icons.destroy', $icon->id))->assertBadRequest();

    Storage::assertExists($icon->path);
});

it('deletes an unused icon with its file', function () {
    $icon = libraryIcon();

    $this->deleteJson(route('admin.masterdata.icons.destroy', $icon->id))->assertOk();

    expect(Icon::query()->find($icon->id))->toBeNull();

    Storage::assertMissing($icon->path);
});

it('serves the icon file with a locked down content policy', function () {
    $icon = libraryIcon();

    $response = $this->get(route('admin.masterdata.icons.file', $icon->id));

    $response->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'none'");
});

it('lists icons with their usage through the datagrid', function () {
    $icon = libraryIcon();
    iconType(['icon_id' => $icon->id]);

    $this->getJson(route('admin.masterdata.icons.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonFragment(['code' => $icon->code, 'usage_count' => 1]);

    $this->get(route('admin.masterdata.icons.index'))->assertOk();
    $this->get(route('admin.masterdata.icons.create'))->assertOk();
    $this->get(route('admin.masterdata.icons.edit', $icon->id))->assertOk()->assertSee($icon->url(), false);
});

it('offers library icons and the manage link on the entity type form', function () {
    $icon = libraryIcon();

    $this->get(route('admin.masterdata.entity_types.edit', iconType()->id))
        ->assertOk()
        ->assertSee(EntityTypeRequest::LIBRARY_ICON_PREFIX.$icon->id, false)
        ->assertSee(route('admin.masterdata.icons.index'), false);
});

it('previews library icons in the entity type icon dropdown', function () {
    $icon = libraryIcon();

    $options = app(EntityTypeFormOptions::class)->formData(iconType())['iconOptions'];

    expect(collect($options)->firstWhere('id', EntityTypeRequest::LIBRARY_ICON_PREFIX.$icon->id))
        ->toMatchArray(['iconUrl' => $icon->url(), 'iconMask' => true]);
});

it('renders a library icon as a theme coloured mask on the dashboard', function () {
    $icon = libraryIcon();
    iconType(['icon_id' => $icon->id, 'status' => 'active']);

    $this->get(route('admin.masterdata.dashboard.index'))
        ->assertOk()
        ->assertSee('class="icon-mask', false)
        ->assertSee("--icon-mask-url: url('".$icon->url()."')", false);
});

it('renders a raster icon as an image instead of a mask', function () {
    createIcon(UploadedFile::fake()->image('photo.png', 64, 64))->assertOk();
    $icon = Icon::query()->latest('id')->firstOrFail();
    iconType(['icon_id' => $icon->id, 'status' => 'active']);

    $grid = $this->getJson(route('admin.masterdata.icons.index'), ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json('records.0.path');

    expect($grid)->toContain('<img')->not->toContain('icon-mask');

    $this->get(route('admin.masterdata.dashboard.index'))
        ->assertOk()
        ->assertSee('<img', false)
        ->assertSee($icon->url(), false)
        ->assertDontSee("--icon-mask-url: url('".$icon->url(), false);
});

it('guards the icon library by permission', function () {
    $icon = libraryIcon();

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->get(route('admin.masterdata.icons.index'))->assertForbidden();
    $this->get(route('admin.masterdata.icons.file', $icon->id))->assertForbidden();
    createIcon()->assertForbidden();

    $this->loginWithPermissions('custom', ['dashboard', 'masterdata', 'masterdata.icons']);

    $this->get(route('admin.masterdata.icons.file', $icon->id))->assertOk();
    $this->deleteJson(route('admin.masterdata.icons.destroy', $icon->id))->assertForbidden();
});

it('seeds the packaged master data icons once', function () {
    Icon::query()->delete();

    $this->seed(IconLibrarySeeder::class);

    $count = Icon::query()->count();
    $factory = Icon::query()->where('code', 'factory')->firstOrFail();

    expect($count)->toBe(count(glob(IconLibrarySeeder::SOURCE_DIRECTORY.'/*.svg')))
        ->and($factory->name)->toBe(trans('masterdata::app.icons.library.factory'))
        ->and($factory->path)->toEndWith('.svg');

    Storage::assertExists($factory->path);

    $this->seed(IconLibrarySeeder::class);

    expect(Icon::query()->count())->toBe($count);
});
