<?php

namespace CodeGreenCreative\LaravelHerokuDeploy\Tests\Feature\Console\Command;

use CodeGreenCreative\LaravelHerokuDeploy\Tests\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PostDeployTest extends TestCase
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
            'api.heroku.com*' => Http::sequence(),
            'api.cloudflare.com/client/v4/zones' => Http::sequence(),
            'api.cloudflare.com/client/v4/zones/*/dns_records' => Http::sequence()
        ]);
    }

    /**
     * Add hostnames to Heroku and Cloudflare successfully
     *
     * @test
     */
    public function addHostNameToHerokuAndCloudflareSuccessful():void
    {
        $this->setUpSequence();

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(0);
    }

    /**
     * Fail retrieving Cloudflare zone record
     *
     * @test
     */
    public function getZoneFromCloudflareFail()
    {
        $this->setUpSequence(500);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }

    /**
     * Test failure to add hostname to Heroku
     *
     * @test
     */
    public function addHostNameToHerokuFail():void
    {
        $this->setUpSequence(200, 500);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }

    /**
     * Test failure to add CNAME to Cloudflare
     *
     * @test
     */
    public function addCnameToCloudflareFail():void
    {
        $this->setUpSequence(200, 200, 500);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }

    /**
     * Test a failure to update config vars in Heroku
     *
     * @test
     */
    public function updateConfigVarsFail():void
    {
        $this->setUpSequence(200, 200, 201, 500);
        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }

    /**
     * Test a failuer to attach an addon to Heroku
     *
     * @test
     */
    public function addHerokuAttachmentFail():void
    {
        $this->setUpSequence(200, 200, 201, 200, 500);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }

    /**
     * Test a failuer to add Automated Certificate Management to Heroku hostname
     *
     * @test
     */
    public function addHerokuAcmFail():void
    {
        $this->setUpSequence(200, 200, 201, 200, 201, 500);

        $this->artisan('heroku:postdeploy')
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
        $hostname = 200,
        $cname = 201,
        $configvars = 200,
        $attachment = 201,
        $acm = 200
    ) {
        // Fake finding all zone records in Cloudflare
        $this->sequences['api.cloudflare.com/client/v4/zones']->push(['result' => array_map(function ($domain) {
            return [
                'id' => \Illuminate\Support\Str::random(32),
                'name' => $domain
            ];
        }, array_keys($this->cloudflare_zones))], $zone);
        foreach ($this->cloudflare_zones as $domain => $subdomains) {
            // Loop through each subdomain for faking additions to Heroku and Cloudflare
            foreach ($subdomains as $key) {
                $subdomain = sprintf('%s.pr-%s.%s', $key, config('heroku-deploy.pr_number'), $domain);
                // For adding subdomain to Heroku
                $this->sequences['api.heroku.com*']->push([
                    'cname' => $subdomain
                ], $hostname);
                // For adding subdomain to Cloudflare
                $this->sequences['api.cloudflare.com/client/v4/zones/*/dns_records']->push([
                    'id' => \Illuminate\Support\Str::random(32),
                    'type' => 'CNAME',
                    'name' => $subdomain
                ], $cname);
            }
        }
        // Finish up the rest of the sequences
        $this->sequences['api.heroku.com*']
            ->pushStatus($configvars) // Update config vars
            ->pushStatus($attachment) // Postgres attachment
            ->pushStatus($acm); // ACM

        Http::fake($this->sequences->toArray());
    }
}
