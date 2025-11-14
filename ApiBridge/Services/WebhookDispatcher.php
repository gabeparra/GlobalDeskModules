<?php

namespace Modules\ApiBridge\Services;

use GuzzleHttp\Client;
use Modules\ApiBridge\Entities\Webhook;
use Modules\ApiBridge\Entities\WebhookLog;
use Psr\Log\LoggerInterface;
use TorMorten\Eventy\Facades\Eventy;

class WebhookDispatcher
{
    protected Client $client;

    protected PayloadFormatter $formatter;

    protected ApiKeyManager $apiKeyManager;

    protected LoggerInterface $logger;

    public function __construct(
        PayloadFormatter $formatter,
        ApiKeyManager $apiKeyManager,
        LoggerInterface $logger
    ) {
        $this->client = new Client([
            'timeout' => (int) config('apibridge.webhook_timeout', 30),
        ]);
        $this->formatter = $formatter;
        $this->apiKeyManager = $apiKeyManager;
        $this->logger = $logger;
    }

    /**
     * @param Webhook $webhook
     * @param string $event
     * @param mixed $payload
     * @param WebhookLog|null $existingLog
     * @return bool
     */
    public function dispatch(Webhook $webhook, string $event, $payload, ?WebhookLog $existingLog = null): bool
    {
        $formatted = $this->formatter->format($payload, true, '', [
            'without_threads' => false,
        ]);

        $formatted = Eventy::filter('apibridge.webhook.before_dispatch', $formatted, $event, $payload, $webhook);

        $signature = $this->signPayload($formatted, $webhook);

        try {
            $response = $this->client->post($webhook->url, [
                'json' => $formatted,
                'headers' => array_filter([
                    'Content-Type' => 'application/json',
                    'X-FreeScout-Event' => $event,
                    'X-FreeScout-Signature' => $signature,
                    'X-ApiBridge-Key' => $this->apiKeyManager->currentKey(),
                ]),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('ApiBridge webhook dispatch failed', [
                'url' => $webhook->url,
                'event' => $event,
                'message' => $e->getMessage(),
            ]);

            WebhookLog::record($webhook, $event, $formatted, null, $e->getMessage(), $existingLog);
            $webhook->forceFill([
                'last_run_time' => now(),
                'last_run_error' => $e->getMessage(),
            ])->save();

            return false;
        }

        $statusCode = $response->getStatusCode();

        WebhookLog::record($webhook, $event, $formatted, $statusCode, null, $existingLog);

        $webhook->forceFill([
            'last_run_time' => now(),
            'last_run_error' => $statusCode >= 200 && $statusCode < 300 ? null : "HTTP {$statusCode}",
        ])->save();

        return $statusCode >= 200 && $statusCode < 300;
    }

    protected function signPayload(array $payload, Webhook $webhook): ?string
    {
        if (!function_exists('hash_hmac')) {
            $this->logger->warning('hash_hmac function not available. Webhook signatures disabled.');
            return null;
        }

        $secret = $webhook->secret ?: config('apibridge.api_key_salt', '');

        if (!$secret) {
            return null;
        }

        return base64_encode(hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES), $secret, true));
    }
}


