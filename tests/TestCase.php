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
            'heroku-deploy.cloudflare_zones',
            json_decode("{\"mydomain.com\": [\"id\", \"account\", \"support\", \"policies\"]}", true)
        );
        // Set addon attachments
        $app['config']->set(
            'heroku-deploy.heroku_addon_attachments',
            json_decode("[{\"addon_id\": \"confirming_app (id or name)\"}]", true)
        );
        // Set a pull request number for testing
        $app['config']->set('heroku-deploy.pr_number', 897);
    }
}
