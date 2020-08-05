<?php

namespace CodeGreenCreative\LaravelHerokuDeploy\Tests\Feature\Console\Command;

use CodeGreenCreative\LaravelHerokuDeploy\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PrPredestroyTest extends TestCase
{
    private $cloudflare_zones;
    private $sequences;

    /**
     * Set up some vars for the tests
     */
    public function setUp():void
    {
        parent::setUp();

        $this->cloudflare_zones = config('heroku-deploy.cloudflare_zones');
        $this->sequences = new Collection([
            'api.cloudflare.com/client/v4/*' => Http::sequence()
        ]);
    }

    /**
     * [testDeleteZoneCnamesFromCloudflareSuccessful description]
     *
     * @test
     */
    public function deleteZoneCnamesFromCloudflareSuccessful()
    {
        $this->setUpSequence();

        $this->artisan('heroku:pr-predestroy')
            ->assertExitCode(0);
    }

    /**
     * [testCloudflareGetZonesFail description]
     *
     * @test
     */
    public function cloudflareGetZonesFail()
    {
        $this->setUpSequence(500);

        $this->artisan('heroku:pr-predestroy')
            ->assertExitCode(1);
    }

    /**
     * [testCloudflareGetZonesFail description]
     *
     * @test
     */
    public function cloudflareGetDnsFail()
    {
        $this->setUpSequence(200, 500);

        $this->artisan('heroku:pr-predestroy')
            ->assertExitCode(1);
    }

    /**
     * [testCloudflareGetZonesFail description]
     *
     * @test
     */
    public function cloudflareDeleteZonesFail()
    {
        $this->setUpSequence(200, 200, 500);

        $this->artisan('heroku:pr-predestroy')
            ->assertExitCode(1);
    }

    /**
     * Set up sequence for HTTP requests
     *
     * @param integer $zone       [description]
     * @param integer $hostname   [description]
     * @param integer $cname      [description]
     * @param integer $configvars [description]
     * @param integer $attachment [description]
     * @param integer $acm        [description]
     */
    public function setUpSequence(
        $zone = 200,
        $get = 200,
        $delete = 200
    ) {
        // Fake finding all zone records in Cloudflare
        $this->sequences['api.cloudflare.com/client/v4/*']->push(['result' => array_map(function ($domain) {
            return [
                'id' => \Illuminate\Support\Str::random(32),
                'name' => $domain
            ];
        }, array_keys($this->cloudflare_zones))], $zone);
        foreach ($this->cloudflare_zones as $domain => $subdomains) {
            // For getting all CNAME records to check agains our subdomains list
            $this->sequences['api.cloudflare.com/client/v4/*']->push([
                'result' => array_map(function ($key) use ($domain) {
                    $subdomain = sprintf('%s.pr-%s.%s', $key, config('heroku-deploy.pr_number'), $domain);
                    return [
                        'id' => \Illuminate\Support\Str::random(32),
                        'name' => $subdomain
                    ];
                }, $subdomains)], $get);
            // Loop through each subdomain for faking deletions in Cloudflare
            foreach ($subdomains as $key) {
                // Delete sequence
                $this->sequences['api.cloudflare.com/client/v4/*']->pushStatus($delete);
            }
        }

        Http::fake($this->sequences->toArray());
    }
}
