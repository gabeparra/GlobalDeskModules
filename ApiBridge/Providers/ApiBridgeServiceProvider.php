<?php

namespace Modules\ApiBridge\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Modules\ApiBridge\Bootstrap\Schedule;
use Modules\ApiBridge\Bootstrap\Settings;
use Modules\ApiBridge\Bootstrap\WebhookListeners;
use Modules\ApiBridge\Support\ApiBridge;

class ApiBridgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerFactories();
        $this->registerRoutes();
        $this->registerCommands();
        $this->registerHooks();
    }

    public function register(): void
    {
        $this->app->singleton(ApiBridge::class, fn () => new ApiBridge());
        $this->aliasMiddleware();
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('apibridge.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'apibridge');
    }

    protected function registerTranslations(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../Resources/lang');
    }

    protected function registerViews(): void
    {
        $viewPath = resource_path('views/modules/apibridge');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/apibridge';
        }, config('view.paths', [])), [$sourcePath]), 'apibridge');
    }

    protected function registerFactories(): void
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    protected function registerRoutes(): void
    {
        if (file_exists(__DIR__ . '/../Http/routes.php')) {
            require __DIR__ . '/../Http/routes.php';
        }
    }

    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\ApiBridge\Console\WebhooksProcess::class,
            \Modules\ApiBridge\Console\WebhooksCleanLogs::class,
        ]);
    }

    protected function registerHooks(): void
    {
        Settings::register();
        Schedule::register();
        WebhookListeners::register();
    }

    protected function aliasMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('apibridge.auth', \Modules\ApiBridge\Http\Middleware\AuthenticateWithApiKey::class);
        $router->aliasMiddleware('apibridge.cors', \Modules\ApiBridge\Http\Middleware\CorsHeaders::class);
    }

    public function provides(): array
    {
        return [];
    }
}


