<?php

namespace LaravelHerokuDeploy\Traits;

use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;

trait ConcernsHerokuReviewApps
{
    /**
     * Token used to make API calls to the Heroku Platform API
     *
     * @var null|string
     */
    private $heroku_token = null;

    /**
     * Token used to make API calls to the Cloudflare API
     *
     * @var null|string
     */
    private $cloudflare_token = null;

    /**
     * Cloudflare zone along with subdomains we are modifying
     *
     * @var array
     */
    private $cloudflare_zones = [
        'mentors.com' => [
            'id' => 'e113cf1a548950222e26d61bb7b0c44a',
            'subdomains' => ['id', 'account', 'support', 'policies']
        ]
    ];

    /**
     * The name of the Heroku review app
     *
     * @var null|string
     */
    private $heroku_app_name = null;

    /**
     * The ID of the staging Postgresql addon for attaching
     *
     * @var string
     */
    private $herok_pgsql_addon = '07a200a0-f00e-466a-8981-aaae418cad8f';

    /**
     * The App name we will be confirming the addon attachement
     *
     * @var string
     */
    private $herok_addon_confirmation_app = 'mentors-account-api-staging';

    /**
     * The pull request number retrieved from Heroku, but supplied by Github
     *
     * @var null|int
     */
    private $heroku_pr_number = null;

    public function __construct()
    {
        parent::__construct();

        $this->heroku_token = config('services.heroku.token');
        $this->cloudflare_token = config('services.cloudflare.token');
        $this->heroku_app_name = config('services.heroku.app_name');
        $this->heroku_pr_number = config('services.heroku.pr_number');
    }

    /**
     * Craft the hostname to be added or removed
     *
     * @param  string $record
     * @return string
     */
    public function getHostname($subdomain, $domain)
    {
        return sprintf('%s.%s.%s', $subdomain, sprintf('pr-%s', $this->heroku_pr_number), $domain);
    }

    /**
     * Add hostname to Heroku using their Platform API
     *
     * @param string $domain
     * @param string $subdomain
     *
     * @return Response
     */
    public function addHostnameToHerokuApp($domain, $subdomain)
    {
        return Http::withToken($this->heroku_token)
            ->withHeaders([
                'Accept' => 'application/vnd.heroku+json; version=3'
            ])->post(sprintf('https://api.heroku.com/apps/%s/domains', $this->heroku_app_name), [
                'hostname' => $this->getHostname($domain, $subdomain)
            ]);
    }

    /**
     * Add CNAME to Cloudflare zone
     *
     * @param string $zone
     * @param string $cname
     * @param string $domain
     * @param string $subdomain
     *
     * @return void
     */
    public function addCnameToCloudflareZone($zone, $cname, $domain, $subdomain)
    {
        // Create DNS record in Cloudflare
        Http::withToken($this->cloudflare_token)
            ->post(sprintf('https://api.cloudflare.com/client/v4/zones/%s/dns_records', $zone), [
                'type' => 'CNAME',
                'name' => $this->getHostname($domain, $subdomain),
                'content' => $cname
            ]);
    }

    /**
     * Handle an Http response
     *
     * @param  \Illuminate\Http\Client\Response $response [description]
     * @return void
     */
    public function handleResponse(\Illuminate\Http\Client\Response $response)
    {
        if (! $response->successful()) {
            try {
                $response->throw();
            } catch (\Illuminate\Http\Client\RequestException $e) {
                // Notify Bugsnag of the exception and continue to the next one
                Bugsnag::notifyException($e);
                return false;
            }
        }

        return true;
    }
}
