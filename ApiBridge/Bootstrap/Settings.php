<?php

namespace Modules\ApiBridge\Bootstrap;

use Modules\ApiBridge\Support\ApiBridge;
use Modules\ApiBridge\Support\WebhookEvents;
use TorMorten\Eventy\Facades\Eventy;

class Settings
{
    public static function register(): void
    {
        Eventy::addFilter('settings.sections', function ($sections) {
            $sections[ApiBridge::MODULE_KEY] = [
                'title' => __('API Bridge'),
                'icon' => 'flash',
                'order' => 610,
            ];

            return $sections;
        }, 40);

        Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section !== ApiBridge::MODULE_KEY) {
                return $settings;
            }

            $settings['apibridge.api_key_salt'] = config('apibridge.api_key_salt');
            $settings['apibridge.cors_hosts'] = config('apibridge.cors_hosts');

            return $settings;
        }, 20, 2);

        Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section !== ApiBridge::MODULE_KEY) {
                return $params;
            }

            /** @var ApiBridge $bridge */
            $bridge = app(ApiBridge::class);

            $mailboxes = \App\Mailbox::orderBy('name')->get();

            return [
                'template_vars' => [
                    'api_key' => $bridge->keyManager()->currentKey(),
                    'webhooks' => $bridge->registry()->all(),
                    'events' => WebhookEvents::all(),
                    'mailboxes' => $mailboxes,
                ],
                'settings' => [
                    'apibridge.api_key_salt' => [
                        'env' => 'APIBRIDGE_API_KEY_SALT',
                    ],
                    'apibridge.cors_hosts' => [
                        'env' => 'APIBRIDGE_CORS_HOSTS',
                    ],
                ],
            ];
        }, 20, 2);

        Eventy::addFilter('settings.view', function ($view, $section) {
            if ($section !== ApiBridge::MODULE_KEY) {
                return $view;
            }

            return 'apibridge::settings';
        }, 20, 2);
    }
}


