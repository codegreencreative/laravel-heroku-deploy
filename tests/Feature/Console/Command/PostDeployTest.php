<?php

namespace Tests\Feature\Console\Command;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class PostDeployTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAddHostNameToHerokuAndCloudflareSuccessful()
    {
        Http::fake([
            'api.heroku.com*' => Http::sequence()
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // id.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // account.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // support.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // policies.
                ->pushStatus(200) // Update config vars
                ->pushStatus(201) // Postgres attachment
                ->pushStatus(200), // ACM
            'api.cloudflare.com/client/v4/zones/*/dns_records' => Http::response([], 201),
        ]);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(0);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAddHostNameToHerokuFail()
    {
        Http::fake([
            'api.heroku.com*' => Http::sequence()
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 500) // id.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 500) // account.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 500) // support.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 500) // policies.
                ->pushStatus(200) // Update config vars
                ->pushStatus(201) // Postgres attachment
                ->pushStatus(200) // ACM
        ]);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAddCnameToCloudflareFail()
    {
        Http::fake([
            'api.heroku.com' => Http::sequence()
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // id.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // account.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // support.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // policies.
                ->pushStatus(200) // Update config vars
                ->pushStatus(201) // Postgres attachment
                ->pushStatus(200), // ACM
            'api.cloudflare.com/client/v4/zones/*/dns_records' => Http::response([], 500),
        ]);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUpdateConfigVarsFail()
    {
        Http::fake([
            'api.heroku.com' => Http::sequence()
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // id.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // account.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // support.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // policies.
                ->pushStatus(500) // Update config vars
                ->pushStatus(201) // Postgres attachment
                ->pushStatus(200), // ACM
            'api.cloudflare.com/client/v4/zones/*/dns_records' => Http::response([], 201),
        ]);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testAddHerokuAttachmentFail()
    {
        Http::fake([
            'api.heroku.com' => Http::sequence()
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // id.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // account.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // support.
                ->push(['cname' => sprintf('%s.heroku.com', Str::random(16))], 201) // policies.
                ->pushStatus(200) // Update config vars
                ->pushStatus(500) // Postgres attachment
                ->pushStatus(200), // ACM
            'api.cloudflare.com/client/v4/zones/*/dns_records' => Http::response([], 201),
        ]);

        $this->artisan('heroku:postdeploy')
            ->assertExitCode(1);
    }
}
