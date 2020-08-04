<?php

namespace CodeGreenCreative\LaravelHerokuDeploy\Tests\Feature\Console\Command;

use CodeGreenCreative\LaravelHerokuDeploy\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PrPredestroyTest extends TestCase
{
    /**
     * [testDeleteZoneCnamesFromCloudflareSuccessful description]
     *
     * @test
     */
    public function deleteZoneCnamesFromCloudflareSuccessful()
    {
        $cloudflare_zones = config('heroku-deploy.cloudflare_zones');
        $zones = new Collection;
        foreach ($cloudflare_zones as $domain => $subdomains) {
            foreach ($subdomains as $subdomain) {
                $zones->push([
                    'id' => \Illuminate\Support\Str::random(32),
                    'type' => 'CNAME',
                    'name' => sprintf('%s.pr-%s.%s', $subdomain, config('heroku-deploy.heroku.pr_number'), $domain)
                ]);
            }
        }

        // Fake our API calls to Cloudflare
        Http::fake([
            'api.cloudflare.com/client/v4/zones' => Http::response(['result' => $zones], 200),
            'api.cloudflare.com/client/v4/zones/*/dns_records*' => Http::sequence()
                ->push(['result' => $zones], 200)
                ->pushStatus(200)
                ->pushStatus(200)
                ->pushStatus(200)
                ->pushStatus(200)
        ]);

        $this->artisan('heroku:pr-predestroy')
            ->assertExitCode(0);
    }

    /**
     * [testCloudflareGetZonesFail description]
     */
    public function cloudflareGetZonesFail()
    {
        // Fake our API calls to Cloudflare
        Http::fake([
            'api.cloudflare.com/client/v4/zones/*/dns_records*' => Http::response('', 500)
        ]);

        $this->artisan('heroku:pr-predestroy')
            ->assertExitCode(1);
    }

    /**
     * [testCloudflareGetZonesFail description]
     */
    public function cloudflareDeleteZonesFail()
    {
        $subdomains = ['id', 'account', 'support', 'policies'];
        $zones = array_map(function ($subdomain) {
            return [
                'id' => \Illuminate\Support\Str::random(32),
                'type' => 'CNAME',
                'name' => sprintf('%s.pr-%s.mentors.com', $subdomain, config('services.heroku.pr_number'))
            ];
        }, $subdomains);

        // Fake our API calls to Cloudflare
        Http::fake([
            'api.cloudflare.com/client/v4/zones/*/dns_records*' => Http::sequence()
                ->push(['result' => $zones], 200)
                ->pushStatus(500)
                ->pushStatus(500)
                ->pushStatus(500)
                ->pushStatus(500)
        ]);

        $this->artisan('heroku:pr-predestroy')
            ->assertExitCode(0);
    }
}
