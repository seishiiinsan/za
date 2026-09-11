<?php

namespace App\Providers;

use App\Support\Front;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Un front actif par requête.
        $this->app->scoped(Front::class);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
        Vite::prefetch(concurrency: 3);
    }
}
