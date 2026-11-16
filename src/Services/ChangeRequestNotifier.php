<?php

namespace Webkul\MasterDataManagement\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Mail;
use Webkul\Notification\Events\NotificationEvent;
use Webkul\Notification\Mail\MailConfiguration;
use Webkul\Notification\Mail\UserNotify;
use Webkul\User\Repositories\AdminRepository;

/**
 * Sends an approval notice to admins: one in-app notification for all of them and, when `masterdata.notifications.email`
 * is on and the install has a mail transport, the same notice by e-mail with a link to the page it names. Each admin
 * gets their own e-mail, so recipients never see each other's addresses.
 */
class ChangeRequestNotifier
{
    /**
     * The e-mail template the notices are rendered with.
     */
    public const string TEMPLATE = 'masterdata::emails.change-request';

    public function __construct(
        protected AdminRepository $adminRepository,
        protected MailConfiguration $mailConfiguration,
        protected Repository $config,
    ) {}

    /**
     * Notify the admins, unless notifications are off or there is nobody to notify.
     *
     * @param  list<int>  $adminIds
     * @param  array<string, mixed>  $routeParams
     * @param  array<string, mixed>  $context
     */
    public function send(
        string $type,
        array $adminIds,
        string $route,
        array $routeParams,
        string $title,
        string $description,
        string $action,
        array $context = [],
        ?string $comment = null,
    ): void {
        if ($adminIds === [] || ! $this->config->get('notifications.enabled', true)) {
            return;
        }

        event(new NotificationEvent([
            'type'         => $type,
            'route'        => $route,
            'route_params' => $routeParams,
            'title'        => $title,
            'description'  => $comment === null ? $description : $description.' '.trans('masterdata::app.approvals.notification.comment', ['comment' => $comment]),
            'context'      => $context,
            'user_ids'     => $adminIds,
        ]));

        if (! $this->mailable()) {
            return;
        }

        $templateData = [
            'title'       => $title,
            'description' => $description,
            'comment'     => $comment,
            'url'         => route($route, $routeParams),
            'action'      => $action,
        ];

        foreach ($this->emails($adminIds) as $email) {
            Mail::queue(new UserNotify([$email], $title, self::TEMPLATE, $templateData));
        }
    }

    /**
     * Whether the notices also go out by e-mail.
     */
    protected function mailable(): bool
    {
        return (bool) $this->config->get('masterdata.notifications.email', true) && $this->mailConfiguration->isConfigured();
    }

    /**
     * The e-mail addresses of the admins.
     *
     * @param  list<int>  $adminIds
     * @return list<string>
     */
    protected function emails(array $adminIds): array
    {
        return $this->adminRepository->getModel()->newQuery()
            ->whereKey($adminIds)
            ->whereNotNull('email')
            ->orderBy('id')
            ->pluck('email')
            ->map(fn (mixed $email): string => (string) $email)
            ->filter()
            ->values()
            ->all();
    }
}
