<?php

namespace Webkul\MasterDataManagement\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Webkul\Core\Models\LocaleProxy;
use Webkul\MasterDataManagement\Enums\AliasScope;
use Webkul\MasterDataManagement\Models\CodeListProxy;
use Webkul\MasterDataManagement\Models\FieldProxy;
use Webkul\MasterDataManagement\Models\SourceSystemProxy;
use Webkul\MasterDataManagement\Repositories\AliasRepository;
use Webkul\MasterDataManagement\Services\Normalizer;

class AliasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return bouncer()->hasPermission('masterdata.code_lists.edit');
    }

    /**
     * A global alias has no scope id, and blank optional selects are stored as null.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('scope_type') === AliasScope::Global->value) {
            $this->merge(['scope_id' => null]);
        }

        foreach (['scope_id', 'locale', 'source_system_id'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $scope = $this->scope();

        $scopeTable = match ($scope) {
            AliasScope::CodeList => CodeListProxy::modelClass(),
            AliasScope::Field    => FieldProxy::modelClass(),
            default              => null,
        };

        return [
            'scope_type'       => ['required', Rule::enum(AliasScope::class)],
            'scope_id'         => $scopeTable === null
                ? ['nullable', 'prohibited']
                : ['required', 'integer', Rule::exists($scopeTable, 'id')],
            'term'             => ['required', 'string', 'max:255', $this->normalizesToText(...), $this->isNotDuplicate(...)],
            'target'           => ['required', 'string', 'max:255'],
            'locale'           => ['nullable', 'string', Rule::exists(LocaleProxy::modelClass(), 'code')],
            'source_system_id' => ['nullable', 'integer', Rule::exists(SourceSystemProxy::modelClass(), 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'scope_type'       => trans('masterdata::app.aliases.form.scope-type'),
            'scope_id'         => trans('masterdata::app.aliases.form.scope'),
            'term'             => trans('masterdata::app.aliases.form.term'),
            'target'           => trans('masterdata::app.aliases.form.target'),
            'locale'           => trans('masterdata::app.aliases.form.locale'),
            'source_system_id' => trans('masterdata::app.aliases.form.source-system'),
        ];
    }

    /**
     * The submitted scope, or null when it is not a valid scope type.
     */
    public function scope(): ?AliasScope
    {
        $type = $this->input('scope_type');

        return is_string($type) ? AliasScope::tryFrom($type) : null;
    }

    /**
     * Fail when the term folds to nothing, since such an alias could never match.
     */
    protected function normalizesToText(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && resolve(Normalizer::class)->normalize($value) === '') {
            $fail('masterdata::app.aliases.validation.empty-term')->translate();
        }
    }

    /**
     * Fail when another alias already maps the normalised term in the same scope and source system.
     */
    protected function isNotDuplicate(string $attribute, mixed $value, Closure $fail): void
    {
        $scope = $this->scope();

        if (! is_string($value) || ! $scope instanceof AliasScope) {
            return;
        }

        $normalized = resolve(Normalizer::class)->normalize($value);

        if ($normalized === '') {
            return;
        }

        $scopeId = $scope === AliasScope::Global ? null : $this->integerOrNull('scope_id');

        $routeId = $this->route('id');

        if (resolve(AliasRepository::class)->duplicateExists(
            $scope,
            $scopeId,
            $normalized,
            $this->integerOrNull('source_system_id'),
            $routeId ? (int) $routeId : null,
        )) {
            $fail('masterdata::app.aliases.validation.duplicate')->translate();
        }
    }

    protected function integerOrNull(string $key): ?int
    {
        $value = $this->input($key);

        return is_numeric($value) ? (int) $value : null;
    }
}
