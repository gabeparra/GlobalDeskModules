@php
    $apiKey = $template_vars['api_key'];
    $webhooks = $template_vars['webhooks'];
    $events = $template_vars['events'];
    $mailboxes = $template_vars['mailboxes'];
    $corsHosts = $settings['apibridge.cors_hosts'] ?? '';
@endphp

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{{ __('API Credentials') }}</h3>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('apibridge.regenerate_key') }}" class="form-horizontal">
            @csrf
            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('API Key') }}</label>
                <div class="col-sm-6">
                    <div class="input-group input-sized-lg">
                        <input type="text" class="form-control disabled" readonly value="{{ $apiKey }}">
                        <span class="input-group-btn">
                            <button class="btn btn-default">{{ __('Regenerate') }}</button>
                        </span>
                    </div>
                    <p class="form-help">{{ __('Share this key with trusted integrations only.') }}</p>
                </div>
            </div>
        </form>

        <form method="POST" action="{{ route('apibridge.config') }}" class="form-horizontal">
            @csrf
            <div class="form-group">
                <label class="col-sm-2 control-label">{{ __('Allowed CORS Origins') }}</label>
                <div class="col-sm-6">
                    <input type="text" name="cors_hosts" value="{{ old('cors_hosts', $corsHosts) }}" class="form-control input-sized-lg">
                    <p class="form-help">
                        {{ __("Comma separated list. Use * to allow any origin.") }}
                    </p>
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-6 col-sm-offset-2">
                    <button class="btn btn-primary">{{ __('Save CORS Settings') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h3 class="panel-title">{{ __('Create Webhook') }}</h3>
    </div>
    <div class="panel-body">
        <form method="POST" action="{{ route('apibridge.webhooks.store') }}" class="form-horizontal">
            @csrf
            @include('apibridge::partials.webhook-form', ['webhook' => null])
            <div class="form-group">
                <div class="col-sm-6 col-sm-offset-2">
                    <button class="btn btn-primary">{{ __('Add Webhook') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if ($webhooks->isNotEmpty())
    @foreach ($webhooks as $webhook)
        <div class="panel panel-shaded panel-padded">
            <form method="POST" action="{{ route('apibridge.webhooks.update', $webhook) }}" class="form-horizontal">
                @csrf
                @method('PUT')
                @include('apibridge::partials.webhook-form', ['webhook' => $webhook])
                <div class="form-group">
                    <div class="col-sm-6 col-sm-offset-2">
                        <button class="btn btn-primary">{{ __('Save') }}</button>
                        <button class="btn btn-link text-danger" form="delete-webhook-{{ $webhook->id }}" type="button" onclick="document.getElementById('delete-webhook-{{ $webhook->id }}').submit();">
                            {{ __('Delete') }}
                        </button>
                    </div>
                </div>
            </form>
            <form id="delete-webhook-{{ $webhook->id }}" method="POST" action="{{ route('apibridge.webhooks.delete', $webhook) }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    @endforeach
@endif





