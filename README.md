[![Latest Version on Packagist](https://img.shields.io/packagist/v/codegreencreative/laravel-samlidp.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-samlidp)
[![Total Downloads](https://img.shields.io/packagist/dt/codegreencreative/laravel-samlidp.svg?style=flat-square)](https://packagist.org/packages/codegreencreative/laravel-samlidp)

# Laravel Heroky Deploy

This package allows you to configure your Heroku Review Apps instance for Laravel applications. You can manage custom domains using Cloudflare, apply Automated Certificate Management (ACM) with Lets Encrypt and update Config Vars using Heroku's `postdeploy` and `pr-predestroy` script events.

## Installation

Require this package with composer:

```shell
composer require codegreencreative/laravel-heroku-deploy:^1.0
```

# Configuration

The command below will add a new heroku-deploy.php config file in your config folder.

```shell
php artisan vendor:publish --tag="heroky_deploy_config"
```

## heroku-deploy.php

```php
// config/heroku-deploy.php

'disks' => [

        ...

        'samlidp' => [
            'driver' => 'local',
            'root' => storage_path() . '/samlidp',
        ]
],
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

Within your login view, probably `resources/views/auth/login.blade.php` add the SAMLRequest directive beneath the CSRF directive:

```php
@csrf
@samlidp
```

The SAMLRequest directive will fill out the hidden input automatically when a SAMLRequest is sent by an HTTP request and therefore initiate a SAML authentication attempt. To initiate the SAML auth, the login and redirect processes need to be intervened. This is done using the Laravel events fired upon authentication.

## Config

After you publish the config file, you will need to set up your Service Providers. The key for the Service Provider is a base 64 encoded Consumer Service (ACS) URL. You can get this information from your Service Provider, but you will need to base 64 encode the URL and place it in your config. This is due to config dot notation.

You may use this command to help generate a new SAML Service Provider:

```shell
php artisan samlidp:sp
```

Example SP in `config/samlidp.php` file:

```php
<?php

return [
    // The URI to your login page
    'login_uri' => 'login',
    // The URI to the saml metadata file, this describes your idP
    'issuer_uri' => 'saml/metadata',
    // List of all Service Providers
    'sp' => [
        // Base64 encoded ACS URL
        'aHR0cHM6Ly9teWZhY2Vib29rd29ya3BsYWNlLmZhY2Vib29rLmNvbS93b3JrL3NhbWwucGhw' => [
            // ACS URL of the Service Provider
            'destination' => 'https://example.com/saml/acs',
            // Simple Logout URL of the Service Provider
            'logout' => 'https://example.com/saml/sls',
        ]
    ]

];
```

## Log out of IdP after SLO

If you wish to log out of the IdP after SLO has completed, set `LOGOUT_AFTER_SLO` to `true` in your `.env` perform the logout action on the Idp.

```
// .env

LOGOUT_AFTER_SLO=true
```

## Redirect to SLO initiator after logout

If you wish to return the user back to the SP by which SLO was initiated, you may provide an additional query parameter to the `/saml/logout` route, for example:

```
https://idp.com/saml/logout?redirect_to=mysp.com
```

After all SP's have been logged out of, the user will be redirected to `mysp.com`. For this to work properly you need to add the `sp_slo_redirects` option to your `config/samlidp.php` config file, for example:

```php
<?php

// config/samlidp.php

return [
    // If you need to redirect after SLO depending on SLO initiator
    // key is beginning of HTTP_REFERER value from SERVER, value is redirect path
    'sp_slo_redirects' => [
        'mysp.com' => 'https://mysp.com',
    ],

];
```

## Attributes (optional)

Service providers may require more additional attributes to be sent via assertion. Its even possible that they require the same information but as a different Claim Type.

By Default this package will send the following Claim Types:

`ClaimTypes::EMAIL_ADDRESS` as `auth()->user()->email`
`ClaimTypes::GIVEN_NAME` as `auth()->user()->name`

This is because Laravel migrations, by default, only supply email and name fields that are usable by SAML 2.0.

To add additional Claim Types, you can subscribe to the Assertion event:

`CodeGreenCreative\SamlIdp\Events\Assertion`

Subscribing to the Event:

In your `App\Providers\EventServiceProvider` class, add to the already existing `$listen` property...

```php
protected $listen = [
    'App\Events\Event' => [
        'App\Listeners\EventListener',
    ],
    'CodeGreenCreative\SamlIdp\Events\Assertion' => [
        'App\Listeners\SamlAssertionAttributes'
    ]
];
```

Sample Listener:

```php
<?php

namespace App\Listeners;

use LightSaml\ClaimTypes;
use LightSaml\Model\Assertion\Attribute;
use CodeGreenCreative\SamlIdp\Events\Assertion;

class SamlAssertionAttributes
{
    public function handle(Assertion $event)
    {
        $event->attribute_statement
            ->addAttribute(new Attribute(ClaimTypes::PPID, auth()->user()->id))
            ->addAttribute(new Attribute(ClaimTypes::NAME, auth()->user()->name));
    }
}

```
