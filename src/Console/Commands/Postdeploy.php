<?php

namespace LaravelHerokuDeploy\Console\Commands;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LaravelHerokuDeploy\Traits\ConcernsHerokuReviewApps;

class Postdeploy extends Command
{
    use ConcernsHerokuReviewApps;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'heroku:postdeploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add domains to Heroku and update Cloudflare DNS after a review app is created.';

    /**
     * Execute the console command.
     *
     * @see https://medium.com/clutter-engineering/heroku-review-apps-with-custom-domains-8edfc0a2b153
     * @return int
     */
    public function handle()
    {
        // Collect cnames to add to Cloudflare
        $host_cnames = new Collection;
        // Loop through each of the zones we need to modify
        foreach ($this->cloudflare_zones as $domain => $zone) {
            // Each subdomain needs to be added
            foreach ($zone['subdomains'] as $subdomain) {
                // Add hostname to Heroku App, if an exception is thrown,
                // we will need to rebuild the app
                $hostname = $this->getHostname($subdomain, $domain);
                $response = Http::withToken($this->heroku_token)
                    ->withHeaders([
                        'Accept' => 'application/vnd.heroku+json; version=3'
                    ])->post(sprintf('https://api.heroku.com/apps/%s/domains', $this->heroku_app_name), [
                        'hostname' => $hostname
                    ]);
                // There was a problem with adding the domain to Heroku
                if (! $this->handleResponse($response)) {
                    return 1;
                }
                // Push cnames on to the collection
                $host_cnames->push([
                    'zone' => $zone['id'],
                    'hostname' => $hostname,
                    'cname' => $response->json()['cname']
                ]);
            }
        }
        // Loop through the domains added into heroku
        foreach ($host_cnames as $host_cname) {
            $response = Http::withToken($this->cloudflare_token)
                ->post(sprintf('https://api.cloudflare.com/client/v4/zones/%s/dns_records', $host_cname['zone']), [
                    'type' => 'CNAME',
                    'name' => $host_cname['hostname'],
                    'content' => $host_cname['cname']
                ]);

            if (! $this->handleResponse($response)) {
                return 1;
            }
        };
        // Update APP_BASE_DOMAIN to match our review app
        $response = Http::withToken(config('services.heroku.token'))
            ->withHeaders([
                'Accept' => 'application/vnd.heroku+json; version=3'
            ])->patch(sprintf('https://api.heroku.com/apps/%s/config-vars', $this->heroku_app_name), [
                'APP_BASE_DOMAIN' => sprintf('pr-%s.mentors.com', $this->heroku_pr_number),
                'APP_URL' => sprintf('https://id.pr-%s.mentors.com', $this->heroku_pr_number)
            ]);

        if (! $this->handleResponse($response)) {
            return 1;
        }

        // Attach staging postgres database to work with review apps
        $response = Http::withToken(config('services.heroku.token'))
            ->withHeaders([
                'Accept' => 'application/vnd.heroku+json; version=3'
            ])->post('https://api.heroku.com/addon-attachments', [
                'addon' => $this->herok_pgsql_addon, // This could change
                'app' => $this->heroku_app_name,
                'confirm' => $this->herok_addon_confirmation_app
            ]);

        if (! $this->handleResponse($response)) {
            return 1;
        }

        // Enable ACM (SSL) NOT IMPLEMENTING DUE TO LETS ENCRYPT RATE LIMITING
        $response = Http::withToken(config('services.heroku.token'))
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/vnd.heroku+json; version=3'
            ])->post(sprintf('https://api.heroku.com/apps/%s/acm', $this->heroku_app_name));

        return $this->handleResponse($response) ? 0 : 1;
    }
}
