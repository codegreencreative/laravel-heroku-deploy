<?php

namespace CodeGreenCreative\LaravelHerokuDeploy\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use CodeGreenCreative\LaravelHerokuDeploy\LaravelHerokuDeployServiceProvider;

class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [LaravelHerokuDeployServiceProvider::class];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageAliases($app)
    {
        return [];
    }

    public function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set(
            'services.heroku.cloudflare_zones',
            "{\"mydomain.com\": [\"id\", \"account\", \"support\", \"policies\"]}"
        );
        $app['config']->set(
            'heroku_addon_attachments',
            "[{\"addon_id\": \"confirming_app (id or name)\"}]"
        );
    }
}