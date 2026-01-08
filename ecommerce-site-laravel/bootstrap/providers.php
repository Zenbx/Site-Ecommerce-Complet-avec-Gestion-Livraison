<?php

return [
    App\Providers\AppServiceProvider::class,

    // Load Telescope only in local environment
    ...app()->environment('local') ? [
        App\Providers\TelescopeServiceProvider::class,
    ] : [],
];
