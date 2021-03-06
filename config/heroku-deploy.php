<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Laravel Heroku Dploy configuration file
    |--------------------------------------------------------------------------
    |
    | Use this file to configure Laravel Heroku Deploy
    |
     */

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
    'enable_acm' => env('HEROKU_DEPLOY_ENABLE_ACM', true),
    // Primary domain for review app
    'primary_domain' => env('HEROKU_DEPLOY_PRIMARY_DOMAIN', null)
];
