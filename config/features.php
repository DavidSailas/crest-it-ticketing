<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Feature switches
    |--------------------------------------------------------------------------
    | Parts of the system that are built and kept in the code but switched off.
    | To turn one back on, set it in .env and run: php artisan optimize:clear
    |
    |   FEATURE_ANNOUNCEMENTS=true
    |
    | Announcements: the admin "post an update to everyone" tool, the What's-new
    | pop-up, the dashboard banner, and the megaphone icon in the top bar.
    */
    'announcements' => (bool) env('FEATURE_ANNOUNCEMENTS', false),

];
