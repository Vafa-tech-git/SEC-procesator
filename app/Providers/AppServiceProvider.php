<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
      Vite::prefetch(concurrency: 3);
      \App\Models\Filing::observe(\App\Observers\FilingObserver::class);
      RateLimiter::for('news-updates', function (Request $request){
        return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
      });
    }
}
