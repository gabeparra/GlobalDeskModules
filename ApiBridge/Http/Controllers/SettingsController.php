<?php

namespace Modules\ApiBridge\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ApiBridge\Entities\Webhook;
use Modules\ApiBridge\Services\ApiKeyManager;
use Modules\ApiBridge\Services\WebhookRegistry;

class SettingsController extends Controller
{
    protected ApiKeyManager $keys;

    protected WebhookRegistry $webhooks;

    public function __construct(ApiKeyManager $keys, WebhookRegistry $webhooks)
    {
        $this->keys = $keys;
        $this->webhooks = $webhooks;
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $key = $this->keys->regenerate();

        \Session::flash('flash_success_floating', __('API key regenerated.'));

        return redirect()->route('settings', ['section' => 'apibridge'])
            ->with('apibridge_key', $key);
    }

    public function storeWebhook(Request $request): RedirectResponse
    {
        $data = $this->validateWebhook($request);

        $this->webhooks->create($data);

        \Session::flash('flash_success_floating', __('Webhook created.'));

        return redirect()->route('settings', ['section' => 'apibridge']);
    }

    public function updateWebhook(Webhook $webhook, Request $request): RedirectResponse
    {
        $data = $this->validateWebhook($request);

        $this->webhooks->update($webhook, $data);

        \Session::flash('flash_success_floating', __('Webhook updated.'));

        return redirect()->route('settings', ['section' => 'apibridge']);
    }

    public function deleteWebhook(Webhook $webhook): RedirectResponse
    {
        $this->webhooks->delete($webhook);

        \Session::flash('flash_success_floating', __('Webhook removed.'));

        return redirect()->route('settings', ['section' => 'apibridge']);
    }

    public function updateConfig(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'cors_hosts' => ['nullable', 'string'],
        ]);

        \Setting::set('apibridge.cors_hosts', $data['cors_hosts']);
        \Setting::save();

        config(['apibridge.cors_hosts' => $data['cors_hosts']]);

        \Session::flash('flash_success_floating', __('Settings saved.'));

        return redirect()->route('settings', ['section' => 'apibridge']);
    }

    protected function validateWebhook(Request $request): array
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
        if (!empty($data['mailboxes'])) {
            $data['mailboxes'] = array_values(array_filter(array_map('intval', $data['mailboxes'])));
            if (!$data['mailboxes']) {
                $data['mailboxes'] = null;
            }
        } else {
            $data['mailboxes'] = null;
        }

        return $data;
    }
}


