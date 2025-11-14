@php
    $selectedEvents = old('events', optional($webhook)->events ?? []);
    $selectedMailboxes = old('mailboxes', optional($webhook)->mailboxes ?? []);
    $secret = old('secret', optional($webhook)->secret);
@endphp

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Webhook URL') }}</label>
    <div class="col-sm-6">
        <input type="url" name="url" class="form-control input-sized-lg" required value="{{ old('url', optional($webhook)->url) }}">
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Events') }}</label>
    <div class="col-sm-6">
        <select name="events[]" class="form-control" multiple required size="10">
            @foreach ($events as $event)
                <option value="{{ $event }}" {{ in_array($event, $selectedEvents, true) ? 'selected' : '' }}>
                    {{ $event }}
                </option>
            @endforeach
        </select>
        <p class="form-help">{{ __('Hold Ctrl or Cmd to select multiple events.') }}</p>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Mailboxes') }}</label>
    <div class="col-sm-6">
        @foreach ($mailboxes as $mailbox)
            <div class="checkbox">
                <label>
                    <input type="checkbox" name="mailboxes[]" value="{{ $mailbox->id }}" {{ in_array($mailbox->id, $selectedMailboxes ?? [], true) ? 'checked' : '' }}>
                    {{ $mailbox->name }}
                </label>
            </div>
        @endforeach
        <p class="form-help">{{ __('Leave all unchecked to listen on every mailbox.') }}</p>
    </div>
</div>

<div class="form-group">
    <label class="col-sm-2 control-label">{{ __('Secret') }}</label>
    <div class="col-sm-6">
        <input type="text" name="secret" class="form-control input-sized-lg" value="{{ $secret }}">
        <p class="form-help">{{ __('Optional secret used to sign webhook payloads (HMAC SHA-256).') }}</p>
    </div>
</div>


