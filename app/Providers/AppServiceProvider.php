<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Request;
use App\Policies\RequestPolicy;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        Request::class => RequestPolicy::class,
    ];

    public function boot(): void
    {
        Gate::policy(Request::class, RequestPolicy::class);
    }
}
