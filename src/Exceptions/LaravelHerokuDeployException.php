<?php

namespace CodeGreenCreative\LaravelHerokuDeploy\Exceptions;

use Exception;

class LaravelHerokuDeployException extends Exception
{
    /**
     * [report description]
     *
     * @return void
     */
    public function report()
    {
        if (class_exists(\Bugsnag\BugsnagLaravel\Facades\Bugsnag::class)) {
            \Bugsnag\BugsnagLaravel\Facades\Bugsnag::notifyException($this);
        }
    }
}
