<?php

namespace Webkul\MasterDataManagement\Http\Controllers\Concerns;

use Webkul\User\Models\Admin;

trait ResolvesAdmin
{
    /**
     * The admin signed in to the panel, if any.
     */
    protected function admin(): ?Admin
    {
        $admin = auth()->guard('admin')->user();

        return $admin instanceof Admin ? $admin : null;
    }
}
