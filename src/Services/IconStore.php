<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use Webkul\Core\Filesystem\FileStorer;

class IconStore
{
    public const string DIRECTORY = 'masterdata/icons';

    /**
     * @var list<string>
     */
    public const array EXTENSIONS = ['svg', 'png', 'webp'];

    public const int MAX_KILOBYTES = 256;

    public const int MIN_PIXELS = 24;

    public function __construct(protected FileStorer $fileStorer) {}

    /**
     * Store the upload as the payload's path, run the save, then delete the file it replaced.
     *
     * A failed save deletes the newly stored file instead, so neither path leaves an orphan behind.
     *
     * @template TResult
     *
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): TResult  $save
     * @return TResult
     */
    public function save(array $payload, File|UploadedFile|null $upload, ?string $currentPath, callable $save): mixed
    {
        $newPath = $upload ? $this->store($upload) : null;

        if ($newPath) {
            $payload['path'] = $newPath;
        }

        try {
            $result = $save($payload);
        } catch (Throwable $exception) {
            $this->delete($newPath);

            throw $exception;
        }

        if ($newPath && $currentPath !== $newPath) {
            $this->delete($currentPath);
        }

        return $result;
    }

    /**
     * Store an uploaded or packaged icon under a random name; an SVG is sanitized on the way in.
     *
     * Content sniffing reads an SVG that opens with a comment as plain text, so the file's own extension is the fallback.
     */
    public function store(File|UploadedFile $file): string
    {
        $extension = strtolower((string) $file->guessExtension());

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $extension = strtolower($file instanceof UploadedFile ? $file->getClientOriginalExtension() : $file->getExtension());
        }

        return $this->fileStorer->storeAs(self::DIRECTORY, Str::uuid()->toString().'.'.$extension, $file);
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    /**
     * Stream the icon from the disk with a content policy that keeps an SVG inert even when opened directly.
     *
     * The admin serves the file itself because a CSS mask fetches in CORS mode, which a bucket on another origin
     * would refuse without its own CORS setup.
     */
    public function response(string $path): StreamedResponse
    {
        abort_unless(Storage::exists($path), 404);

        $mimeType = str_ends_with($path, '.svg')
            ? 'image/svg+xml'
            : (Storage::mimeType($path) ?: 'application/octet-stream');

        return Storage::response($path, null, [
            'Content-Type'            => $mimeType,
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options'  => 'nosniff',
            'Cache-Control'           => 'private, max-age=31536000, immutable',
        ]);
    }
}
