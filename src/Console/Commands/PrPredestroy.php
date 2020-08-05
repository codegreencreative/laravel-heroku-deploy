<?php

namespace CodeGreenCreative\LaravelHerokuDeploy\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use CodeGreenCreative\LaravelHerokuDeploy\Traits\ConcernsHerokuReviewApps;
use CodeGreenCreative\LaravelHerokuDeploy\Exceptions\LaravelHerokuDeployException;

class PrPredestroy extends Command
{
    use ConcernsHerokuReviewApps;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'heroku:pr-predestroy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cloudflare DNS entries for each custom domain in Heroku after a review app is deleted.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // Catch \Illuminate\Http\Client\RequestException's
        try {
            // Get ALL zones for this account
            $response = $this->cloudflare('get', 'zones');
            $zones = collect($response->json()['result']);
            // Loop through each of the zones we need to modify
            foreach ($this->cloudflare_zones as $domain => $subdomains) {
                // Find zone record
                $zone = $zones->firstWhere('name', $domain);
                // Get current DNS zone records from Cloudflare
                $response = $this->cloudflare('get', sprintf('zones/%s/dns_records', $zone['id']), [
                    'type' => 'CNAME'
                ]);

                $current_zones = collect($response->json()['result']);

                // Each subdomain needs to be deleted
                foreach ($subdomains as $subdomain) {
                    $record = $current_zones->firstWhere('name', $this->getHostname($subdomain, $domain));
                    if (is_null($record)) {
                        throw new LaravelHerokuDeployException('Review app hostname not found when removing zone.');
                    }
                    // Attempt to remove the dns record
                    $response = $this->cloudflare(
                        'delete',
                        sprintf('zones/%s/dns_records/%s', $zone['id'], $record['id'])
                    );
                }
                return 0;
            }
        } catch (LaravelHerokuDeployException $e) {
            return 1;
        }
    }
}
