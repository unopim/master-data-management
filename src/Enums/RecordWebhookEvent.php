<?php

namespace Webkul\MasterDataManagement\Enums;

/**
 * The master record events a webhook can subscribe to.
 */
enum RecordWebhookEvent: string
{
    case Created = 'masterdata.record.created';
    case Updated = 'masterdata.record.updated';
    case Retired = 'masterdata.record.retired';
    case Reactivated = 'masterdata.record.reactivated';
    case Merged = 'masterdata.record.merged';
    case Unmerged = 'masterdata.record.unmerged';
    case Erased = 'masterdata.record.erased';

    /**
     * Every event keyed by its value, mapped to its label translation key, for the webhook event registry.
     *
     * @return array<string, string>
     */
    public static function registry(): array
    {
        $events = [];

        foreach (self::cases() as $case) {
            $events[$case->value] = 'masterdata::app.webhooks.events.'.strtolower($case->name);
        }

        return $events;
    }
}
