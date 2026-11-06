<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Webkul\MasterDataManagement\Services\ModelTransfer\ModelBundleImporter;

class ModelImportRequest extends FormRequest
{
    /**
     * @var array<mixed>|null
     */
    protected ?array $bundle = null;

    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.entity_types.model_import');
    }

    /**
     * The upload must be a JSON file within the size limit; its name is never used, only its content is read.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file'    => ['required', 'file', 'extensions:json', 'mimetypes:application/json,text/plain', 'max:'.(int) config('masterdata.model_transfer.max_file_kb', 5120)],
            'publish' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file'    => trans('masterdata::app.model-transfer.import.file'),
            'publish' => trans('masterdata::app.model-transfer.import.publish'),
        ];
    }

    /**
     * Decode the uploaded bundle once the file itself is valid.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $file = $this->file('file');

                if ($validator->errors()->has('file') || ! $file instanceof UploadedFile) {
                    return;
                }

                try {
                    $this->bundle = resolve(ModelBundleImporter::class)->decode((string) $file->get());
                } catch (ValidationException $exception) {
                    $validator->errors()->merge($exception->errors());
                }
            },
        ];
    }

    /**
     * The decoded bundle of the validated upload.
     *
     * @return array<mixed>
     */
    public function bundle(): array
    {
        return $this->bundle ?? [];
    }
}
