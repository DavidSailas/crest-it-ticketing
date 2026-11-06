<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company email domain
    |--------------------------------------------------------------------------
    | Used only to SUGGEST addresses on Admin > Users > Email addresses
    | (username + @domain). Nothing is ever sent to a suggestion until an admin
    | saves it and the person confirms it.
    */
    'email_domain' => env('COMPANY_EMAIL_DOMAIN', 'crestforwarderinc.com'),

];
