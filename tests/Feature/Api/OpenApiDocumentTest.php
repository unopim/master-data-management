<?php

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\MasterDataManagement\Services\OpenApiDocument;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->document = app(OpenApiDocument::class)->toArray();
});

it('is an openapi 3.1 document with the api security schemes', function () {
    expect($this->document['openapi'])->toStartWith('3.1.')
        ->and($this->document['info'])->toHaveKeys(['title', 'version'])
        ->and($this->document['paths'])->toBeArray()->not->toBeEmpty()
        ->and($this->document['components']['securitySchemes'])->toHaveKeys(['bearerAuth', 'oauth2'])
        ->and($this->document['components']['securitySchemes']['oauth2']['flows']['password']['tokenUrl'])->toBe(url('/oauth/token'))
        ->and($this->document['servers'][0]['url'])->toBe(url('/api'))
        ->and($this->document['security'])->toBe([['bearerAuth' => []], ['oauth2' => []]]);
});

it('documents every registered master data api route by method and path', function () {
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => Str::startsWith($route->uri(), 'api/v1/rest/masterdata'))
        ->flatMap(fn (RoutingRoute $route): array => array_map(
            fn (string $method): string => strtolower($method).' '.Str::after($route->uri(), 'api'),
            array_diff($route->methods(), ['HEAD']),
        ))
        ->sort()
        ->values()
        ->all();

    $documented = collect($this->document['paths'])
        ->flatMap(fn (array $operations, string $path): array => array_map(
            fn (string $method): string => $method.' '.$path,
            array_keys(array_intersect_key($operations, array_flip(['get', 'post', 'put', 'patch', 'delete']))),
        ))
        ->sort()
        ->values()
        ->all();

    expect($routes)->not->toBeEmpty()
        ->and($documented)->toBe($routes);
});

it('resolves every schema reference and documents the path parameters of each operation', function () {
    $json = json_encode($this->document);

    preg_match_all('~"\$ref":"#\\\\/components\\\\/(schemas|parameters|responses)\\\\/([A-Za-z0-9_]+)"~', $json, $matches, PREG_SET_ORDER);

    foreach ($matches as [, $section, $name]) {
        expect($this->document['components'][$section])->toHaveKey($name);
    }

    foreach ($this->document['paths'] as $path => $operations) {
        preg_match_all('/\{([^}]+)\}/', $path, $placeholders);

        foreach (array_intersect_key($operations, array_flip(['get', 'post', 'put', 'patch', 'delete'])) as $method => $operation) {
            $names = collect([...($operations['parameters'] ?? []), ...($operation['parameters'] ?? [])])
                ->map(fn (array $parameter): array => isset($parameter['$ref'])
                    ? $this->document['components']['parameters'][Str::afterLast($parameter['$ref'], '/')]
                    : $parameter)
                ->where('in', 'path')
                ->pluck('name')
                ->all();

            expect($operation)->toHaveKeys(['operationId', 'responses'])
                ->and($names)->toEqualCanonicalizing($placeholders[1], "$method $path");
        }
    }
});

it('serves the document as json behind the api authentication', function () {
    $this->getJson(route('admin.api.masterdata.openapi'))->assertUnauthorized();

    $this->getJson(route('admin.api.masterdata.openapi'), $this->getAuthenticationHeaders())
        ->assertOk()
        ->assertJsonPath('openapi', $this->document['openapi'])
        ->assertJsonPath('info.title', $this->document['info']['title']);
});
