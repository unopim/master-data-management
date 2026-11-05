<?php

namespace Webkul\MasterDataManagement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\MasterDataManagement\Contracts\IdempotencyKey as IdempotencyKeyContract;

/**
 * The response a REST write sent for an API client's `Idempotency-Key`, replayed when the client repeats the key.
 *
 * @property array{body?: string, headers?: array<string, string>}|null $response
 */
#[Fillable(['api_client_id', 'key', 'request_hash', 'status_code', 'response'])]
#[Table(name: 'mdm_idempotency_keys')]
class IdempotencyKey extends Model implements IdempotencyKeyContract
{
    public const UPDATED_AT = null;

    /**
     * Typecasts the defined columns into given types.
     */
    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'response'    => 'array',
        ];
    }
}
