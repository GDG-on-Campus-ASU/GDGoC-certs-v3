<?php

namespace App\Listeners;

use App\Models\LoginLog;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        LoginLog::create([
            'email' => isset($event->credentials['email'])
                ? $event->credentials['email']
                : 'missing email; keys: ['.implode(', ', array_diff(array_keys($event->credentials), ['password'])).']',
            'ip_address' => request()->ip(),
            'success' => false,
        ]);
    }
}
