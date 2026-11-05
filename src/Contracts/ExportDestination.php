<?php

namespace Webkul\MasterDataManagement\Contracts;

/**
 * Where a finished master data export is published for downstream consumers such as a data lake.
 */
interface ExportDestination
{
    /**
     * Copy an export file from the private disk to the destination and return the location it was written to.
     */
    public function publish(string $privatePath, string $targetPath): string;
}
