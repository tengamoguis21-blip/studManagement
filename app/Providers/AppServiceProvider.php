<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (getenv('CODESPACE_NAME')) {
            $domain = getenv('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN') ?: 'app.github.dev';
            $url = 'https://'.getenv('CODESPACE_NAME').'-8000.'.$domain;
            config(['app.url' => $url]);
        }
        // Artisan serve reloads .env and filters inherited environment variables.
        // The startup script also persists APP_URL so HTTP requests use it.
        $url = config('app.url');
        if (str_starts_with($url, 'https://')) {
            config(['session.secure' => true]);
            \Illuminate\Support\Facades\URL::forceRootUrl($url);
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}