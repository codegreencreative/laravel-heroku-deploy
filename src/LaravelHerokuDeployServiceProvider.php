<?php

namespace CodeGreenCreative\LaravelHerokuDeploy;

/**
 * The service provider for laravel-samleidp
 *
 * @license MIT
 */

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;

class LaravelHerokuDeployServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot(Router $router)
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();
        $this->offerPublishing();
        $this->registerCommands();
    }

    /**
     * Configure the service provider
     *
     * @return void
     */
    private function configure()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/heroku-deploy.php', 'heroku-deploy');
    }

    /**
     * Offer publishing for the service provider
     *
     * @return void
     */
    public function offerPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/heroku-deploy.php' => config_path('heroku-deploy.php'),
            ], 'heroku_deploy_config');
        }
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateCertificate::class,
                CreateServiceProvider::class,
            ]);
        }
    }
}
