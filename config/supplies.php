<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Main supply branch
    |--------------------------------------------------------------------------
    | The branch code whose stock EVERY branch may request from (Cebu holds the
    | main IT stock and the IT team). Branches without IT of their own, such as
    | Cagayan de Oro and Davao, order from here. Change it with
    | SUPPLY_MAIN_BRANCH=CEB in .env if the main office ever moves.
    */
    'main_branch' => env('SUPPLY_MAIN_BRANCH', 'CEB'),

];
