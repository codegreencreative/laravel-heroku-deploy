<?php

namespace App\Console\Commands;

use App\Console\ConcernsHerokuReviewApps;
use Bugsnag\BugsnagLaravel\Facades\Bugsnag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

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
        // Loop through each of the zones we need to modify
        foreach ($this->cloudflare_zones as $domain => $zone) {
            // Get current DNS zone records from Cloudflare
            $response = Http::withToken($this->cloudflare_token)
                ->get(sprintf('https://api.cloudflare.com/client/v4/zones/%s/dns_records', $zone['id']), [
                    'type' => 'CNAME'
                ]);

            if (! $this->handleResponse($response)) {
                return 1;
            }

            $current_zones = collect($response->json()['result']);

            // Each subdomain needs to be added
            foreach ($zone['subdomains'] as $subdomain) {
                try {
                    $record = $current_zones->firstWhere('name', $this->getHostname($subdomain, $domain));
                    if (is_null($record)) {
                        throw new \App\Exceptions\PrPredestroyException('Review app hostname not found when removing zone.');
                    }
                } catch (\App\Exceptions\PrPredestroyException $e) {
                    // Notify Bugsnag of the exception and continue to the next one
                    Bugsnag::notifyException($e);
                    continue;
                }
                // Attempt to remove the dns record
                $response = Http::withToken($this->cloudflare_token)
                    ->delete(sprintf(
                        'https://api.cloudflare.com/client/v4/zones/%s/dns_records/%s',
                        $zone['id'],
                        $record['id']
                    ));

                $this->handleResponse($response);
            }

            return 0;
        }
    }
}
