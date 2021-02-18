<?php

namespace CodeGreenCreative\LaravelHerokuDeploy\Console\Commands;

use Illuminate\Console\Command;

class InitRepo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'heroku:init-repo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will initialize the current repo with Heroku specific files.';

    /**
     * Execute the console command.
     *
     * @see https://medium.com/clutter-engineering/heroku-review-apps-with-custom-domains-8edfc0a2b153
     * @return int
     */
    public function handle()
    {
        // Maybe create Procfile
        $this->maybeCreateProcfile();
        $this->maybeCreateNginxConfig();
        $this->maybeCreateAppJson();
        $this->maybeCreateDatabaseConfig();
        $this->updateComposerJson();
        // $this->maybeCreateTrustedProxies();

        return 0;
    }

    /**
     * Create Procfile
     * @return [type] [description]
     */
    public function maybeCreateProcfile()
    {
        $procfile = [];
        // Check for Procfile
        if (file_exists(base_path('Procfile'))
            && ! $this->confirm('Procfile already exists, would you like to overwrite? This is a distructive operation!')
        ) {
            return;
        }
        // As long as we need to create, continue creating
        array_push($procfile, 'web: vendor/bin/heroku-php-nginx -C nginx_app.conf public/');
        if ($this->confirm('Will you be using Laravel Horizon?')) {
            array_push($procfile, 'worker: php artisan horizon');
        }
        if ($this->confirm('Will you be using Laravel Scheduling?')) {
            array_push($procfile, 'scheduler: php artisan schedule:daemon');
        }
        try {
            file_put_contents(base_path('Procfile'), implode("\n", $procfile));
        } catch (\Exception $e) {
            $this->error('There was a problem writing to the Procfile file, please check permissions');
            return;
        }

        $this->alert(sprintf('The file %s was created successfully', base_path('Procfile')));
    }

    /**
     * [maybeCreateNginxConfig description]
     * @return [type] [description]
     */
    public function maybeCreateNginxConfig()
    {
        // Check for Procfile and permissin to overwrite
        if (file_exists(base_path('nginx_app.conf'))
            && ! $this->confirm('nginx_app.conf already exists, would you like to overwrite? This is a distructive operation!')
        ) {
            return;
        }
        // Try to do the work to put a new file or overwrite an existing one
        try {
            file_put_contents(
                base_path('nginx_app.conf'),
                file_get_contents(dirname(__DIR__) . '/../../nginx_app.conf')
            );
        } catch (\Exception $e) {
            $this->error('There was a problem writing to the nginx_app.conf file, please check permissions');
            return;
        }
        $this->alert(sprintf('The file %s was created successfully', base_path('nginx_app.conf')));
    }

    /**
     * [maybeCreateAppJson description]
     * @return [type] [description]
     */
    public function maybeCreateAppJson()
    {
        // Check for Procfile and permissin to overwrite
        if (file_exists(base_path('app.json'))
            && ! $this->confirm('app.json already exists, would you like to overwrite? This is a distructive operation!')
        ) {
            return;
        }

        // Do the work to put a new file or overwrite an existing one
        $app = $this->ask('What is your app name? format: %1$s-app?');
        try {
            file_put_contents(
                base_path('app.json'),
                sprintf(file_get_contents(dirname(__DIR__) . '/../../app.json'), $app)
            );
        } catch (\Exception $e) {
            $this->error('There was a problem writing to the app.json file, please check permissions');
            return;
        }
        $this->alert(sprintf('The file %s was created successfully', base_path('app.json')));
    }

    /**
     * [maybeCreateTrustedProxies description]
     * @return [type] [description]
     */
    public function maybeCreateTrustedProxies()
    {
        // Check if wanting to overwrite trusted proxies middleware
        if ($this->confirm('Would you like to initialize TrustProxies middleware? This is a distructive operation!')) {
            try {
                file_put_contents(
                    base_path('app/Http/Middleware/TrustProxies.php'),
                    file_get_contents(dirname(__DIR__) . '/../Http/Middleware/TrustProxies.php')
                );
            } catch (\Exception $e) {
                $this->error('There was a problem writing to the TrustProxies.php file, please check permissions');
                return;
            }
            $this->alert(sprintf('The file %s was created successfully', base_path('app/Http/Middleware/TrustProxies.php')));
        }
    }

    public function maybeCreateDatabaseConfig()
    {
        // Check for database.php config and permissin to overwrite
        if (file_exists(base_path('config/database.php'))
            && ! $this->confirm('database.php config file already exists, would you like to overwrite? This is a distructive operation!')
        ) {
            return;
        }
        // Try to do the work to put a new file or overwrite an existing one
        try {
            file_put_contents(
                base_path('config/database.php'),
                file_get_contents(dirname(__DIR__) . '/../../config/database.php')
            );
        } catch (\Exception $e) {
            $this->error('There was a problem writing to the database.php file, please check permissions');
            return;
        }
        $this->alert(sprintf('The file %s was created successfully', base_path('database.php')));
    }

    public function updateComposerJson()
    {
        // "php": "^7.4",
        // "ext-bcmath": "*",
        // "ext-gd": "*",
        // "ext-redis": "*",
        //
        // Solves issues with bcmath_compat
        // "moontoast/math": "1.1.2 as 1.999.999",
    }
}
