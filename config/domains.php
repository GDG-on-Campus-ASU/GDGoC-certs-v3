<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Domains
    |--------------------------------------------------------------------------
    |
    | Configure the domains used by the application for different purposes.
    | The public domain is used for certificate validation and downloads.
    | The admin domain is used for the admin dashboard and leader portal.
    |
    */

    'public' => env('DOMAIN_PUBLIC', 'certs.gdg-oncampus.dev'),
    'admin' => env('DOMAIN_ADMIN', 'sudo.certs-admin.certs.gdg-oncampus.dev'),

];
