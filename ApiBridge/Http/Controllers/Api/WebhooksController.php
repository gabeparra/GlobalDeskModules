<?php

namespace Modules\ApiBridge\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiBridge\Entities\Webhook;
use Modules\ApiBridge\Services\WebhookRegistry;

class WebhooksController extends ApiController
{
    protected WebhookRegistry $webhooks;

    public function __construct(\Modules\ApiBridge\Services\PayloadFormatter $formatter, WebhookRegistry $webhooks)
    {
        parent::__construct($formatter);
        $this->webhooks = $webhooks;
    }

    public function store(Request $request): JsonResponse
    {
        $webhook = $this->webhooks->create($this->validated($request));

        return $this->respond($this->formatter->format($webhook), 201);
    }

    public function update(Webhook $webhook, Request $request): JsonResponse
    {
        $webhook = $this->webhooks->update($webhook, $this->validated($request));

        return $this->respond($this->formatter->format($webhook));
    }

    public function destroy(Webhook $webhook): JsonResponse
    {
        $this->webhooks->delete($webhook);

        return $this->respond([], 204);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'url' => ['required', 'url'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['string'],
            'mailboxes' => ['nullable', 'array'],
            'mailboxes.*' => ['integer'],
            'secret' => ['nullable', 'string', 'max:255'],
        ]);

        $data['events'] = array_values(array_unique($data['events']));
        $data['mailboxes'] = !empty($data['mailboxes']) ? array_values(array_unique($data['mailboxes'])) : null;

        return $data;
    }
}


