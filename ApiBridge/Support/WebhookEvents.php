<?php

namespace Modules\ApiBridge\Support;

use TorMorten\Eventy\Facades\Eventy;

class WebhookEvents
{
    public static function all(): array
    {
        $default = [
            'conversation.created',
            'conversation.assigned',
            'conversation.deleted',
            'conversation.deleted_permanently',
            'conversation.restored',
            'conversation.moved',
            'conversation.status_changed',
            'conversation.customer_replied',
            'conversation.agent_replied',
            'conversation.note_added',
            'customer.created',
            'customer.updated',
        ];

        return Eventy::filter('apibridge.events', $default);
    }
}


