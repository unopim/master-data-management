<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Http\Middleware\IdempotentRequest;
use Webkul\MasterDataManagement\Models\IdempotencyKey;
use Webkul\MasterDataManagement\Models\Record;
use Webkul\MasterDataManagement\Repositories\EntityTypeRepository;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->type = app(EntityTypeRepository::class)->create(['code' => $this->uniqueCode('line'), 'status' => 'active', 'code_strategy' => 'source_id']);
    $this->storeUrl = route('admin.api.masterdata.records.store', $this->type->code);
    $this->recordCount = fn (): int => Record::query()->where('entity_type_id', $this->type->id)->count();
});

it('replays the stored response for a repeated key', function () {
    $headers = $this->getAuthenticationHeaders() + ['Idempotency-Key' => 'key-1'];

    $first = $this->postJson($this->storeUrl, ['code' => 'L-1', 'label' => 'Line'], $headers)
        ->assertCreated()
        ->assertHeaderMissing('Idempotent-Replayed');

    $second = $this->postJson($this->storeUrl, ['label' => 'Line', 'code' => 'L-1'], $headers)
        ->assertCreated()
        ->assertHeader('Idempotent-Replayed', 'true')
        ->assertHeader('Location', $first->headers->get('Location'))
        ->assertHeader('ETag', '"1"');

    expect($second->json())->toBe($first->json())
        ->and(($this->recordCount)())->toBe(1);
});

it('refuses the same key with a different body', function () {
    $headers = $this->getAuthenticationHeaders() + ['Idempotency-Key' => 'key-1'];

    $this->postJson($this->storeUrl, ['code' => 'L-1'], $headers)->assertCreated();

    $this->postJson($this->storeUrl, ['code' => 'L-2'], $headers)
        ->assertUnprocessable()
        ->assertJsonPath('code', 'idempotency_mismatch');

    expect(($this->recordCount)())->toBe(1);
});

it('refuses a key whose request is still in flight', function () {
    $headers = $this->getAuthenticationHeaders();
    $token = DB::table('oauth_access_tokens')->latest('created_at')->first();

    $lock = Cache::lock('masterdata:idempotency:'.$token->client_id.':'.sha1('key-1'), 30);

    expect($lock->get())->toBeTrue();

    $this->postJson($this->storeUrl, ['code' => 'L-1'], $headers + ['Idempotency-Key' => 'key-1'])
        ->assertStatus(409)
        ->assertJsonPath('code', 'idempotency_in_flight');

    $lock->release();

    expect(($this->recordCount)())->toBe(0);
});

it('rejects a key longer than 255 characters', function () {
    $this->postJson($this->storeUrl, ['code' => 'L-1'], $this->getAuthenticationHeaders() + ['Idempotency-Key' => str_repeat('k', 256)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['idempotency_key']);

    expect(($this->recordCount)())->toBe(0);
});

it('does not store server errors', function () {
    Route::post('api/v1/rest/masterdata/test-idempotency-failure', fn () => response()->json(['message' => 'boom'], 503))
        ->middleware(['auth:api', IdempotentRequest::class]);

    $headers = $this->getAuthenticationHeaders() + ['Idempotency-Key' => 'key-500'];

    $this->postJson('api/v1/rest/masterdata/test-idempotency-failure', ['a' => 1], $headers)->assertStatus(503);

    expect(IdempotencyKey::query()->where('key', 'key-500')->exists())->toBeFalse();
});

it('passes requests without a key through untouched', function () {
    $headers = $this->getAuthenticationHeaders();

    $this->postJson($this->storeUrl, ['code' => 'L-1'], $headers)->assertCreated();
    $this->postJson($this->storeUrl, ['code' => 'L-1'], $headers)->assertUnprocessable();

    expect(IdempotencyKey::query()->count())->toBe(0);
});

it('keeps keys per api client', function () {
    $this->postJson($this->storeUrl, ['code' => 'L-1'], $this->getAuthenticationHeaders() + ['Idempotency-Key' => 'shared'])->assertCreated();

    app('auth')->forgetGuards();

    $this->postJson($this->storeUrl, ['code' => 'L-2'], $this->getAuthenticationHeaders() + ['Idempotency-Key' => 'shared'])->assertCreated();

    expect(($this->recordCount)())->toBe(2)
        ->and(IdempotencyKey::query()->where('key', 'shared')->count())->toBe(2);
});

it('prunes keys older than a day only', function () {
    $old = IdempotencyKey::query()->create(['api_client_id' => 'c', 'key' => 'old', 'request_hash' => str_repeat('a', 64), 'status_code' => 201, 'response' => []]);
    $old->forceFill(['created_at' => now()->subHours(25)])->save();
    IdempotencyKey::query()->create(['api_client_id' => 'c', 'key' => 'fresh', 'request_hash' => str_repeat('a', 64), 'status_code' => 201, 'response' => []]);

    $this->artisan('masterdata:idempotency:prune')->assertSuccessful();

    expect(IdempotencyKey::query()->pluck('key')->all())->toBe(['fresh']);
});

it('schedules the prune daily without overlapping', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'masterdata:idempotency:prune'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 0 * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('hashes the body independent of key order', function () {
    $middleware = app(IdempotentRequest::class);

    $hash = fn (array $body): string => (fn () => $this->requestHash(Request::create('/x', 'POST', $body)))->call($middleware);

    expect($hash(['a' => 1, 'b' => ['y' => 2, 'x' => 1]]))->toBe($hash(['b' => ['x' => 1, 'y' => 2], 'a' => 1]))
        ->and($hash(['a' => 1]))->not->toBe($hash(['a' => 2]));
});
