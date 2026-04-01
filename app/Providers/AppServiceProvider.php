<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\Request;
use App\Policies\RequestPolicy;
use App\View\Components\RequestTimeline;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Request::class => RequestPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Request::class, RequestPolicy::class);
        
        // Manually register components
        Blade::component('request-timeline', RequestTimeline::class);
    }
}
