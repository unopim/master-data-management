<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

/**
 * The admin form follows `redirect_url` and shows the flashed message on the page it opens, so the message is both
 * flashed and returned; core's `respondSaved()` skips the flash for JSON callers.
 */
trait RespondsWithRedirect
{
    /**
     * Flash the message and answer with it and the page the admin form opens next.
     */
    protected function redirectResponse(string $url, string $message, string $type = 'success'): JsonResponse
    {
        session()->flash($type, $message);

        $payload = [
            'redirect_url' => $url,
            'message'      => $message,
        ];

        if ($type !== 'success') {
            $payload['type'] = $type;
        }

        return new JsonResponse($payload);
    }

    /**
     * Tell the admin the change waits for approval and open the given page.
     */
    protected function pendingResponse(string $url): JsonResponse
    {
        return $this->redirectResponse($url, trans('masterdata::app.records.change-pending'), 'warning');
    }
}
