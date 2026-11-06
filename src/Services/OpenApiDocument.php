<?php

namespace Webkul\MasterDataManagement\Services;

/**
 * The OpenAPI 3.1 document of the `/v1/rest/masterdata` API, read from the maintained spec in
 * `Resources/openapi/masterdata.php`. The server and token URLs are built per request from the install's URL, so the
 * document holds under any host, scheme or path prefix.
 */
class OpenApiDocument
{
    /**
     * The document as an array, ready to encode as JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $document = require dirname(__DIR__).'/Resources/openapi/masterdata.php';

        $document['servers'] = [['url' => url('/api'), 'description' => config('app.name')]];

        $document['components']['securitySchemes']['oauth2']['flows']['password']['tokenUrl'] = url('/oauth/token');

        return $document;
    }
}
