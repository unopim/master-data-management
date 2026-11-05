<?php

namespace Webkul\MasterDataManagement\Export;

use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use RuntimeException;
use Webkul\MasterDataManagement\Contracts\ExportDestination;

/**
 * Publishes exports to the filesystem disk named by `masterdata.export_disk`, such as a local disk or an Azure
 * Blob Storage container that ADLS Gen2 and Fabric read.
 */
class FilesystemDiskDestination implements ExportDestination
{
    public function __construct(protected FilesystemFactory $filesystem) {}

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function publish(string $privatePath, string $targetPath): string
    {
        $disk = (string) config('masterdata.export_disk', 'local');

        $stream = $this->filesystem->disk('private')->readStream($privatePath);

        if (! is_resource($stream)) {
            throw new RuntimeException(trans('masterdata::app.exports.records.errors.file-missing', ['path' => $privatePath]));
        }

        try {
            $written = $this->filesystem->disk($disk)->writeStream($targetPath, $stream);
        } finally {
            fclose($stream);
        }

        if ($written === false) {
            throw new RuntimeException(trans('masterdata::app.exports.records.errors.publish-failed', ['disk' => $disk, 'path' => $targetPath]));
        }

        return $disk.':'.$targetPath;
    }
}
