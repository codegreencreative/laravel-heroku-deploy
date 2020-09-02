[![Latest Version on Packagist](https://img.shields.io/packagist/v/codegreencreative/laravel-heroku-deploy.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-heroku-deploy)
[![Total Downloads](https://img.shields.io/packagist/dt/codegreencreative/laravel-heroku-deploy.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-heroku-deploy)

# Laravel Heroku Deploy

This Laravel 7 package allows you to configure your Heroku Review Apps instance for Laravel applications. You can manage custom domains using Cloudflare, apply Automated Certificate Management (ACM) with Lets Encrypt and update Config Vars using Heroku's `postdeploy` and `pr-predestroy` script events.

## Installation

Require this package with composer:

```shell
composer require codegreencreative/laravel-heroku-deploy
```

# Configuration

The command below will add a new heroku-deploy.php config file in your config folder.

```shell
php artisan vendor:publish --tag="heroky_deploy_config"
```

## heroku-deploy.php

Sample heroku-deploy configuration file.

```php
// config/heroku-deploy.php

return [
    // Heroku Platform API token, learn more here:
    // https://devcenter.heroku.com/articles/authentication
    'heroku_token' => env('HEROKU_DEPLOY_HEROKU_TOKEN', null),
    // Cloudflare token, get yours here:
    // https://dash.cloudflare.com/profile/api-tokens
    'cloudflare_token' => env('HEROKU_DEPLOY_CLOUDFLARE_TOKEN', null),
    // Created by Heroku
    'app_name' => env('HEROKU_APP_NAME', null),
    'pr_number' => env('HEROKU_PR_NUMBER', null),
    // JSON array containing information on your zones you want to use for this project
    // {
    //     "mydomain.com": ["id", "account", "support", "policies"]
    // }
    'cloudflare_zones' => json_decode(env('HEROKU_DEPLOY_ZONES', '[]'), true),
    // You can attach addons from other applications
    // {
    //      "addon_id": "confirming_app (id or name)"
    // }
    'heroku_addon_attachments' => json_decode(env('HEROKU_DEPLOY_ADDON_ATTACHMENTS', '[]'), true),
    // Enable automated certificate management in Heroku for each subdomain
    'enable_acm' => env('HEROKU_DEPLOY_ENABLE_ACM', true)
];
```

## .env

Example .env entry.

```
HEROKU_DEPLOY_HEROKU_TOKEN=addyourtokenehere
HEROKU_DEPLOY_CLOUDFLARE_TOKEN=addyourtokenehere
HEROKU_DEPLOY_ZONES="{\"mydomain.com\": [\"id\", \"account\", \"support\"]}"
# Optional
# This connects Heroku Postgres database or Heroku Redis, for example
HEROKU_DEPLOY_ADDON_ATTACHMENTS="{\"xxxx-xxxx-xxxx-xxxx-xxxx\": \"xxxxxxxxx\"}"
HEROKU_DEPLOY_ENABLE_ACM=false
```

## app.json

Add the `postdeploy` and `pr-predestroy` commands to your app.json file.

```json
{
    "environments": {
        "review": {
            "scripts": {
                "postdeploy": "php artisan heroku:postdeploy",
                "pr-predestroy": "php artisan heroku:pr-predestroy"
            }
        }
    }
}  
```

## Config Vars

Two additional config vars are added/updated depending on your own configuration, `APP_BASE_DOMAIN` and `APP_URL`. The _first_ domain you define in `HEROKU_DEPLOY_ZONES` will be considered your __base/primary domain__ as you can only have one. The first subdomain of your first domain is considered your `APP_URL`. We also use the pull request number to keep review apps unique. Pull request numbers are provided by Heroku as environment variables.

For example, these will be added to your environment automatically:

```
APP_BASE_DOMAIN=pr-125.mydomain.com
APP_URL=https://id.pr-125.mydomain.com
```

Should you not enable ACM, session cookies will be set to insecure.

```
SESSION_SECURE_COOKIE=false
```

Session cookies will be created with a unique name.

```
SESSION_COOKIE=PR125_SID
```

Any other config vars that need to be added can be done so in your Heroku pipeline.

## Bug Reporting

If Bugsnag is installed, exceptions will be reported in Bugsnag.
