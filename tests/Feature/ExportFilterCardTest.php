<?php

use Webkul\DataTransfer\Helpers\ExportFilterFields;

it('opts the record exporter into the generic export filters card', function () {
    expect(ExportFilterFields::usesGenericCard(config('exporters.masterdata_records')))->toBeTrue();
});
