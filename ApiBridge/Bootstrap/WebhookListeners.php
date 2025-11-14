<?php

namespace Modules\ApiBridge\Bootstrap;

use App\Conversation;
use Modules\ApiBridge\Support\ApiBridge;
use TorMorten\Eventy\Facades\Eventy;

class WebhookListeners
{
    public static function register(): void
    {
        Eventy::addAction('apibridge.webhook.run', function ($webhook, $event, $payload) {
            /** @var ApiBridge $bridge */
            $bridge = app(ApiBridge::class);
            $bridge->runWebhook($webhook, $event, $payload);
        }, 20, 3);

        Eventy::addAction('conversation.created_by_user', function ($conversation) {
            self::dispatch('conversation.created', $conversation);
        }, 20, 1);

        Eventy::addAction('conversation.created_by_customer', function ($conversation) {
            self::dispatch('conversation.created', $conversation);
        }, 20, 1);

        Eventy::addAction('conversation.user_changed', function ($conversation) {
            if ($conversation->user_id) {
                self::dispatch('conversation.assigned', $conversation);
            }
        }, 20, 1);

        Eventy::addAction('conversation.deleted', function ($conversation) {
            self::dispatch('conversation.deleted', $conversation);
        }, 20, 1);

        Eventy::addAction('conversation.deleting', function ($conversation) {
            self::dispatch('conversation.deleted_permanently', $conversation);
        }, 20, 1);

        Eventy::addAction('conversation.state_changed', function ($conversation, $user, $previousState) {
            if ($previousState === Conversation::STATE_DELETED && $conversation->state === Conversation::STATE_PUBLISHED) {
                self::dispatch('conversation.restored', $conversation);
            }
        }, 20, 3);

        Eventy::addAction('conversation.moved', function ($conversation) {
            self::dispatch('conversation.moved', $conversation);
        }, 20, 1);

        Eventy::addAction('conversation.status_changed', function ($conversation) {
            self::dispatch('conversation.status_changed', $conversation);
        }, 20, 1);

        Eventy::addAction('conversation.customer_replied', function ($conversation, $thread) {
            $conversation->setPreview($thread->body);
            self::dispatch('conversation.customer_replied', $conversation);
        }, 20, 2);

        Eventy::addAction('conversation.user_replied', function ($conversation, $thread) {
            $conversation->setPreview($thread->body);
            self::dispatch('conversation.agent_replied', $conversation);
        }, 20, 2);

        Eventy::addAction('conversation.note_added', function ($conversation) {
            self::dispatch('conversation.note_added', $conversation);
        }, 20, 1);

        Eventy::addAction('customer.created', function ($customer) {
            self::dispatch('customer.created', $customer);
        }, 20, 1);

        Eventy::addAction('customer.updated', function ($customer) {
            self::dispatch('customer.updated', $customer);
        }, 20, 1);
    }

    protected static function dispatch(string $event, $payload): void
    {
        /** @var ApiBridge $bridge */
        $bridge = app(ApiBridge::class);
        $bridge->dispatchEvent($event, $payload);
    }
}


