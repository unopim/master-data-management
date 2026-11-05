<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Webkul\Core\Rules\FileMimeExtensionMatch;
use Webkul\MasterDataManagement\Models\IconProxy;
use Webkul\MasterDataManagement\Services\IconStore;

class IconRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission($this->isCreating() ? 'masterdata.icons.create' : 'masterdata.icons.edit');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => $this->isCreating()
                ? ['required', 'string', 'regex:/^[a-z][a-z0-9_]{1,63}$/', Rule::unique(IconProxy::modelClass(), 'code')]
                : ['prohibited'],
            'name'   => ['required', 'string', 'max:255'],
            'file'   => $this->fileRules(),
            'file.*' => $this->uploadRules(),
        ];
    }

    /**
     * Get the uploaded icon file; the media component submits a new file as a one-item list.
     */
    public function upload(): ?UploadedFile
    {
        $file = $this->file('file');

        $file = is_array($file) ? (array_values($file)[0] ?? null) : $file;

        return $file instanceof UploadedFile ? $file : null;
    }

    /**
     * Get the validated attributes the repository stores; the file path is set by the icon store.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->safe()->only(['code', 'name']);
    }

    /**
     * A new icon needs a file; on edit a kept file comes back as a string, which is read as "unchanged" only.
     *
     * @return array<int, mixed>
     */
    protected function fileRules(): array
    {
        return match (true) {
            $this->file('file') instanceof UploadedFile => $this->uploadRules(),
            is_array($this->file('file'))               => ['array', 'max:1'],
            $this->isCreating()                         => ['required'],
            default                                     => ['required', 'string', 'max:255'],
        };
    }

    /**
     * @return array<int, mixed>
     */
    protected function uploadRules(): array
    {
        $rules = [
            'file',
            'mimes:'.implode(',', IconStore::EXTENSIONS),
            'max:'.IconStore::MAX_KILOBYTES,
            new FileMimeExtensionMatch,
        ];

        if ($this->upload()?->guessExtension() !== 'svg') {
            $rules[] = 'dimensions:min_width='.IconStore::MIN_PIXELS.',min_height='.IconStore::MIN_PIXELS;
        }

        return $rules;
    }

    protected function isCreating(): bool
    {
        return ! $this->route('id');
    }
}
