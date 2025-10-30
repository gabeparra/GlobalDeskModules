<?php

namespace Modules\KnowledgeBaseAPI\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\Facades\Route;

class KnowledgeBaseAPIServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(module_path('KnowledgeBaseAPI', 'Database/Migrations'));
        $this->registerRoutes();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path('KnowledgeBaseAPI', 'Config/config.php') => config_path('knowledgebaseapi.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('KnowledgeBaseAPI', 'Config/config.php'), 'knowledgebaseapi'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/knowledgebaseapi');

        $sourcePath = module_path('KnowledgeBaseAPI', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], ['views', 'knowledgebaseapi-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), 'knowledgebaseapi');
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path('KnowledgeBaseAPI', 'Database/factories'));
        }
    }

    /**
     * Register routes for the module.
     *
     * @return void
     */
    protected function registerRoutes()
    {
        Route::group([
            'middleware' => 'api',
            'prefix' => 'api',
        ], function () {
            $this->loadRoutesFrom(module_path('KnowledgeBaseAPI', 'Routes/api.php'));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (\Config::get('view.paths') as $path) {
            if (is_dir($path . '/modules/knowledgebaseapi')) {
                $paths[] = $path . '/modules/knowledgebaseapi';
            }
        }
        return $paths;
    }
}
