<?php

namespace Tests\Feature\Console\Command;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PrPredestroyTest extends TestCase
{
    /**
     * [testDeleteZoneCnamesFromCloudflareSuccessful description]
     *
     * @return void
     */
    public function testDeleteZoneCnamesFromCloudflareSuccessful()
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
     *
     * @return void
     */
    public function testCloudflareGetZonesFail()
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
     *
     * @return void
     */
    public function testCloudflareDeleteZonesFail()
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
