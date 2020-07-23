<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SAML idP configuration file
    |--------------------------------------------------------------------------
    |
    | Use this file to configure the service providers you want to use.
    |
     */
    // Outputs data to your laravel.log file for debugging
    'debug' => false,
    // Heroku Platform API token, learn more here:
    // https://devcenter.heroku.com/articles/authentication
    'heroku_token' => env('HEROKU_DEPLOY_HEROKU_TOKEN', null),
    // Cloudflare token, get yours here:
    // https://dash.cloudflare.com/profile/api-tokens
    'cloudflare_token' => env('HEROKU_DEPLOY_CLOUDFLARE_TOKEN', null),
    // JSON array containing information on your zones you want to use for this project
    'cloudflare_zones' => env('HEROKU_DEPLOY_ZONES', []),
    // You can attach addons from other applications
    // {
    //      'addon_id': 'confirming_app (id or name)',
    //      '07a200a0-f00e-466a-8981-aaae418cad8f': 'mentors-account-api-staging'
    // }
    'heroku_addon_attachments' => env('HEROKU_DEPLOY_ADDON_ATTACHMENTS', [])
];
