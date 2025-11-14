<?php

namespace Modules\ApiBridge\Support;

use App\Misc\Helper;
use Modules\ApiBridge\Entities\Webhook;
use Modules\ApiBridge\Services\ApiKeyManager;
use Modules\ApiBridge\Services\PayloadFormatter;
use Modules\ApiBridge\Services\WebhookDispatcher;
use Modules\ApiBridge\Services\WebhookRegistry;

class ApiBridge
{
    public const MODULE_KEY = 'apibridge';

    protected WebhookRegistry $registry;

    protected PayloadFormatter $formatter;

    protected ApiKeyManager $keyManager;

    protected WebhookDispatcher $dispatcher;

    public function __construct()
    {
        $this->registry = app(WebhookRegistry::class);
        $this->formatter = app(PayloadFormatter::class);
        $this->keyManager = app(ApiKeyManager::class);
        $this->dispatcher = app(WebhookDispatcher::class);
    }

    public function registry(): WebhookRegistry
    {
        return $this->registry;
    }

    public function formatter(): PayloadFormatter
    {
        return $this->formatter;
    }

    public function keyManager(): ApiKeyManager
    {
        return $this->keyManager;
    }

    public function dispatcher(): WebhookDispatcher
    {
        return $this->dispatcher;
    }

    public function dispatchEvent(string $event, $data): void
    {
        foreach ($this->registry()->forEvent($event) as $webhook) {
            if ($data instanceof \App\Conversation && !$webhook->forMailbox($data->mailbox_id)) {
                continue;
            }

            Helper::backgroundAction('apibridge.webhook.run', [$webhook, $event, $data]);
        }
    }

    public function runWebhook(Webhook $webhook, string $event, $payload): bool
    {
        return $this->dispatcher()->dispatch($webhook, $event, $payload);
    }
}


