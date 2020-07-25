<?php

namespace CodeGreenCreative\LaravelHerokuDeploy\Traits;

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
    private $cloudflare_zones = [];

    /**
     * The name of the Heroku review app
     *
     * @var null|string
     */
    private $heroku_app_name = null;

    /**
     * The pull request number retrieved from Heroku, but supplied by Github
     *
     * @var null|int
     */
    private $heroku_pr_number = null;

    /**
     * The App name we will be confirming the addon attachement
     *
     * @var array
     */
    private $herok_addon_attachments = [];

    /**
     * Switch to add Automated Certificate Management for domains
     *
     * @var boolean
     */
    private $enable_acm;

    public function __construct()
    {
        parent::__construct();

        $this->heroku_token = config('services.heroku.token');
        $this->cloudflare_token = config('services.cloudflare.token');
        $this->heroku_app_name = config('services.heroku.app_name');
        $this->heroku_pr_number = config('services.heroku.pr_number');
        $this->cloudflare_zones = json_decode(config('services.heroku.cloudflare_zones'));
        $this->heroku_addon_attachments = json_decode(config('services.heroku.heroku_addon_attachments'));
        $this->enable_acm = config('services.heroku.enable_acm');
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
     * Make an API request to Heroku Platform API
     *
     * @return Response
     */
    protected function heroku($method, $uri, $params = [], $headers = [])
    {
        $response = Http::withToken($this->heroku_token)
            ->withHeaders(array_merge(['Accept' => 'application/vnd.heroku+json; version=3'], $headers))
            ->$method(sprintf('https://api.heroku.com/%s', $uri), $params);

        if ($this->handleResponse($response)) {
            return $response;
        }
    }

    /**
     * Make an API request to Cloudflare API
     *
     * @return Response
     */
    protected function cloudflare($method, $uri, $params = [], $headers = [])
    {
        $response = Http::withToken($this->cloudflare_token)
            ->withHeaders($headers)
            ->$method(sprintf('https://api.cloudflare.com/client/v4/%s', $uri), $params);

        if ($this->handleResponse($response)) {
            return $response;
        }
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
                if (class_exists(\Bugsnag\BugsnagLaravel\Facades\Bugsnag::class)) {
                    \Bugsnag\BugsnagLaravel\Facades\Bugsnag::notifyException($e);
                }
                // Rethrow the exception to be caught
                throw $e;
            }
        }

        return true;
    }
}
