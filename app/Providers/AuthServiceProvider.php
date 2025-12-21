<?php

namespace App\Providers;

use App\Models\CareRequest;
use App\Policies\CareRequestPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        CareRequest::class => CareRequestPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
