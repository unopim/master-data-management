<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Webkul\MasterDataManagement\Models\CodeList;
use Webkul\MasterDataManagement\Repositories\CodeListRepository;
use Webkul\MasterDataManagement\Repositories\CodeRepository;

/**
 * Installs the shared code lists templates draw on, such as countries and currencies, once per install: a list that
 * already exists under the code is reused as it is, so several templates share one list.
 */
class CodeListInstaller
{
    public function __construct(
        protected TemplateReader $templateReader,
        protected TemplateValidator $templateValidator,
        protected CodeListRepository $codeListRepository,
        protected CodeRepository $codeRepository,
        protected CodeListPublisher $codeListPublisher,
    ) {}

    /**
     * Get the code list with the code, creating it from the shipped file with a published first version when absent.
     *
     * A list created here reports `wasRecentlyCreated`.
     *
     * @param  list<string>  $locales  The locales whose labels are stored.
     *
     * @throws InvalidArgumentException
     * @throws ValidationException
     */
    public function ensure(string $code, array $locales): CodeList
    {
        $existing = $this->codeListRepository->findOneByField('code', $code);

        if ($existing instanceof CodeList) {
            return $existing;
        }

        $definition = $this->templateReader->readCodeList($code);

        $this->templateValidator->validateCodeList($definition);

        $list = $this->codeListRepository->create(['code' => $code] + $this->templateReader->localized('name', $definition['names'], $locales));

        $draft = $this->codeListRepository->createDraftFrom($list);

        $this->codeRepository->insertLabelled((int) $draft->id, array_map(fn (array $entry): array => [
            'code'   => (string) $entry['code'],
            'labels' => array_map(fn (array $label): string => $label['label'], $this->templateReader->localized('label', $entry['labels'], $locales)),
        ], $definition['codes']));

        $this->codeListPublisher->publish($draft);

        return $list;
    }
}
