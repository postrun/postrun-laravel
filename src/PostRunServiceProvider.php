<?php

namespace PostRun\Laravel;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use PostRun\Laravel\Transport\PostRunTransport;

class PostRunServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/postrun.php', 'postrun');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/postrun.php' => config_path('postrun.php'),
        ], 'postrun-config');

        $this->registerTransport();
        $this->registerWebhookRoutes();
    }

    protected function registerTransport(): void
    {
        Mail::extend('postrun', function (array $config) {
            return new PostRunTransport(
                apiKey: $config['api_key'] ?? config('postrun.api_key'),
                endpoint: $config['endpoint'] ?? config('postrun.endpoint'),
            );
        });
    }

    protected function registerWebhookRoutes(): void
    {
        // Only register webhook routes if a secret is configured
        if (config('postrun.webhook.secret')) {
            $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');
        }
    }
}
