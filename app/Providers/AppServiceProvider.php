<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Request;
use App\Policies\RequestPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Request::class => RequestPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
        
        Gate::policy(Request::class, RequestPolicy::class);
    }
}
