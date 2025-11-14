<?php

namespace Modules\ApiBridge\Entities;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    public const MAX_ATTEMPTS = 10;

    protected $table = 'apibridge_webhooks';

    protected $fillable = [
        'url',
        'events',
        'mailboxes',
        'secret',
    ];

    protected $casts = [
        'events' => 'array',
        'mailboxes' => 'array',
        'last_run_time' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(WebhookLog::class);
    }

    public function scopeEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }

    public function forMailbox(?int $mailboxId): bool
    {
        if (!$mailboxId) {
            return true;
        }

        $mailboxes = $this->mailboxes ?? [];

        if (empty($mailboxes)) {
            return true;
        }

        return in_array($mailboxId, $mailboxes, true);
    }

    public function hasSecret(): bool
    {
        return !empty($this->secret);
    }
}



