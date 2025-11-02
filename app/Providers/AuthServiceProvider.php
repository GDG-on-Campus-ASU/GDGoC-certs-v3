<?php

namespace App\Providers;

use App\Models\CertificateTemplate;
use App\Models\EmailTemplate;
use App\Policies\CertificateTemplatePolicy;
use App\Policies\EmailTemplatePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        CertificateTemplate::class => CertificateTemplatePolicy::class,
        EmailTemplate::class => EmailTemplatePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}
