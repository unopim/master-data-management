<?php

namespace Webkul\MasterDataManagement\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Token;
use Symfony\Component\HttpFoundation\Response;
use Webkul\MasterDataManagement\Enums\ApiErrorCode;
use Webkul\MasterDataManagement\Http\Responses\ApiError;
use Webkul\MasterDataManagement\Models\IdempotencyKey;
use Webkul\MasterDataManagement\Repositories\IdempotencyKeyRepository;

/**
 * Replays the stored response when an API client repeats an `Idempotency-Key`, so a retried write never runs twice.
 *
 * Keys belong to the Passport client. A cache lock serializes requests of one key; the unique key column backs it
 * up where the cache store is not shared. The body is stored as sent, so a replay matches it byte for byte.
 * Responses of 500 and above are not stored, so such a request can be retried.
 */
class IdempotentRequest
{
    /**
     * The response headers a replay sends again.
     */
    protected const array REPLAYED_HEADERS = ['Content-Type', 'Location', 'ETag'];

    public function __construct(protected IdempotencyKeyRepository $idempotencyKeyRepository) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! is_string($key) || $key === '') {
            return $next($request);
        }

        if (mb_strlen($key) > 255) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => ['idempotency_key' => [trans('masterdata::app.api.idempotency-key-too-long')]],
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $clientId = $this->clientId($request);

        $hash = $this->requestHash($request);

        $lock = Cache::lock('masterdata:idempotency:'.$clientId.':'.sha1($key), 30);

        if (! $lock->get()) {
            return ApiError::make(ApiErrorCode::IdempotencyInFlight, JsonResponse::HTTP_CONFLICT, trans('masterdata::app.api.idempotency-in-flight'));
        }

        try {
            $stored = $this->idempotencyKeyRepository->findForClient($clientId, $key);

            if ($stored instanceof IdempotencyKey) {
                if (! hash_equals($stored->request_hash, $hash)) {
                    return ApiError::make(ApiErrorCode::IdempotencyMismatch, JsonResponse::HTTP_UNPROCESSABLE_ENTITY, trans('masterdata::app.api.idempotency-mismatch'));
                }

                return response((string) ($stored->response['body'] ?? ''), $stored->status_code, [
                    ...($stored->response['headers'] ?? []),
                    'Idempotent-Replayed' => 'true',
                ]);
            }

            $response = $next($request);

            if ($response->getStatusCode() < 500) {
                $this->idempotencyKeyRepository->remember($clientId, $key, $hash, $response->getStatusCode(), [
                    'body'    => (string) $response->getContent(),
                    'headers' => $this->replayedHeaders($response),
                ]);
            }

            return $response;
        } finally {
            $lock->release();
        }
    }

    /**
     * The Passport client of the access token, or the admin when the request carries no client.
     */
    protected function clientId(Request $request): string
    {
        $user = $request->user();

        $token = is_object($user) && method_exists($user, 'token') ? $user->token() : null;

        $clientId = match (true) {
            $token instanceof AccessToken => $token->oauth_client_id,
            $token instanceof Token       => $token->client_id,
            default                       => null,
        };

        return $clientId !== null && $clientId !== ''
            ? (string) $clientId
            : 'admin:'.(is_object($user) && method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : '');
    }

    /**
     * Hash the method, path and body, with the body's keys sorted so their order does not matter.
     */
    protected function requestHash(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->method(),
            $request->path(),
            (string) json_encode($this->canonical($request->input())),
        ]));
    }

    /**
     * Sort the keys of every associative array, keeping list order.
     */
    protected function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map($this->canonical(...), $value);
    }

    /**
     * The headers of the response a replay sends again.
     *
     * @return array<string, string>
     */
    protected function replayedHeaders(Response $response): array
    {
        $headers = [];

        foreach (self::REPLAYED_HEADERS as $name) {
            if ($response->headers->has($name)) {
                $headers[$name] = (string) $response->headers->get($name);
            }
        }

        return $headers;
    }
}
