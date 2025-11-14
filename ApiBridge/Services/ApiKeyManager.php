<?php

namespace Modules\ApiBridge\Services;

use Illuminate\Support\Str;

class ApiKeyManager
{
    public function currentKey(): string
    {
        return md5(config('app.key') . 'apibridge_key' . config('apibridge.api_key_salt'));
    }

    public function regenerate(): string
    {
        $salt = Str::random(32);
        \Setting::set('apibridge.api_key_salt', $salt);
        \Setting::save();

        config(['apibridge.api_key_salt' => $salt]);

        return $this->currentKey();
    }

    public function verify(?string $providedKey): bool
    {
        if (!$providedKey) {
            return false;
        }

        return hash_equals($this->currentKey(), $providedKey);
    }
}


