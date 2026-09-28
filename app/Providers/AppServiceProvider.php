<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;
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
        // One password policy for the whole app (profile, reset, register and
        // admin-created accounts all use Password::defaults()).
        Password::defaults(fn () => Password::min(8)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols());

        // Railway (and most platforms-as-a-service) terminate HTTPS at the
        // edge and forward plain HTTP to the container, so Laravel sees the
        // request as insecure and generates http:// asset/URL links even
        // though the browser loaded the page over https://. Forcing the
        // scheme here keeps every generated URL (including Vite's compiled
        // CSS/JS tags) consistently https, avoiding mixed-content blocks.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
