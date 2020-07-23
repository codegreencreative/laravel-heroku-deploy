[![Latest Version on Packagist](https://img.shields.io/packagist/v/codegreencreative/laravel-samlidp.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-samlidp)
[![Total Downloads](https://img.shields.io/packagist/dt/codegreencreative/laravel-samlidp.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-samlidp)

# Laravel Heroky Deploy

This package allows you to configure your Heroku Review Apps instance for Laravel applications. You can manage custom domains using Cloudflare, apply Automated Certificate Management (ACM) with Lets Encrypt and update Config Vars using Heroku's `postdeploy` and `pr-predestroy` script events.

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
    // JSON array containing information on your zones you want to use for this project
    // {
    //     "mydomain.com": ["id", "account", "support", "policies"]
    // }
    'cloudflare_zones' => env('HEROKU_DEPLOY_ZONES', []),
    // You can attach addons from other applications
    // {
    //      "addon_id": "confirming_app (id or name)"
    // }
    'heroku_addon_attachments' => env('HEROKU_DEPLOY_ADDON_ATTACHMENTS', [])
];
```

## .env

Example .env entry.

```
HEROKU_DEPLOY_HEROKU_TOKEN=addyourtokenehere
HEROKU_DEPLOY_CLOUDFLARE_TOKEN=addyourtokenehere
HEROKU_DEPLOY_ZONES="{\"mydomain.com\": [\"id\", \"account\", \"support\"]}"
HEROKU_DEPLOY_ADDON_ATTACHMENTS="{\"07a200a0-f00e-466a-8981-aaae418cad8f\": \"my-app-staging\"}"
```

## app.json

Add the postdeploy and pr-predestroy commands to your app.json file.

```json
{
    "scripts": {
        "postdeploy": "php artisan heroku:postdeploy",
        "pr-predestroy": "php artisan heroku:pr-predestroy"
    }, 
}  
```

## Usage
