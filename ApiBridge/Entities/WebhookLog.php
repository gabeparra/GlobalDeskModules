<?php

namespace Modules\ApiBridge\Entities;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $table = 'apibridge_webhook_logs';

    protected $fillable = [
        'webhook_id',
        'event',
        'status_code',
        'error',
        'payload',
        'finished',
        'attempts',
    ];

    protected $casts = [
        'payload' => 'array',
        'finished' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];

    public function webhook()
    {
        return $this->belongsTo(Webhook::class);
    }

    public function markFinished(?string $error = null): void
    {
        $this->finished = true;
        if ($error !== null) {
            $this->error = $error;
        }
        $this->save();
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;

        if ($this->attempts >= Webhook::MAX_ATTEMPTS) {
            $this->finished = true;
        }

        $this->save();
    }

    public static function record(
        Webhook $webhook,
        string $event,
        array $payload,
        ?int $statusCode,
        ?string $error,
        ?self $existing = null
    ): self {
        $log = $existing ?: new self();
        $log->webhook_id = $webhook->id;
        $log->event = $event;
        $log->payload = $payload;
        $log->status_code = $statusCode;
        $log->error = $error;
        $log->finished = $statusCode !== null && $statusCode >= 200 && $statusCode < 300;

        if (!$existing) {
            $log->attempts = 1;
        }

        $log->save();

        return $log;
    }
}





