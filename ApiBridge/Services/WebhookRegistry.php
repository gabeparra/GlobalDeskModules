<?php

namespace Modules\ApiBridge\Services;

use Illuminate\Support\Collection;
use Modules\ApiBridge\Entities\Webhook;

class WebhookRegistry
{
    protected ?Collection $cache = null;

    public function all(): Collection
    {
        if ($this->cache === null) {
            $this->cache = Webhook::orderByDesc('id')->get();
        }

        return $this->cache;
    }

    public function refresh(): void
    {
        $this->cache = Webhook::orderByDesc('id')->get();
    }

    public function forEvent(string $event): Collection
    {
        return $this->all()->filter(function (Webhook $webhook) use ($event) {
            return in_array($event, $webhook->events ?? [], true);
        });
    }

    public function create(array $attributes): Webhook
    {
        $webhook = Webhook::create($attributes);
        $this->refresh();

        return $webhook;
    }

    public function update(Webhook $webhook, array $attributes): Webhook
    {
        $webhook->fill($attributes);
        $webhook->save();
        $this->refresh();

        return $webhook;
    }

    public function delete(Webhook $webhook): void
    {
        $webhook->delete();
        $this->refresh();
    }
}



