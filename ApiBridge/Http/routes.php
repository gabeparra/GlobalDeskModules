<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/** @var Router $router */
$router = app('router');

$router->group([
    'middleware' => ['web', 'auth', 'roles'],
    'roles' => ['admin'],
    'prefix' => \Helper::getSubdirectory(),
    'namespace' => 'Modules\ApiBridge\Http\Controllers',
], function () {
    Route::post('/apibridge/regenerate-key', 'SettingsController@regenerate')->name('apibridge.regenerate_key');
    Route::post('/apibridge/config', 'SettingsController@updateConfig')->name('apibridge.config');
    Route::post('/apibridge/webhooks', 'SettingsController@storeWebhook')->name('apibridge.webhooks.store');
    Route::put('/apibridge/webhooks/{webhook}', 'SettingsController@updateWebhook')->name('apibridge.webhooks.update');
    Route::delete('/apibridge/webhooks/{webhook}', 'SettingsController@deleteWebhook')->name('apibridge.webhooks.delete');
});

$router->group([
    'middleware' => ['bindings', 'apibridge.auth', 'apibridge.cors'],
    'prefix' => \Helper::getSubdirectory(true) . 'api/apibridge',
    'namespace' => 'Modules\ApiBridge\Http\Controllers\Api',
], function () {
    Route::options('{any}', 'CorsController@handle')->where('any', '.*');

    Route::get('/conversations', 'ConversationsController@index');
    Route::get('/conversations/{conversation}', 'ConversationsController@show');

    Route::get('/customers', 'CustomersController@index');
    Route::get('/customers/{customer}', 'CustomersController@show');

    Route::post('/webhooks', 'WebhooksController@store');
    Route::put('/webhooks/{webhook}', 'WebhooksController@update');
    Route::delete('/webhooks/{webhook}', 'WebhooksController@destroy');
});


