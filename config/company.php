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

    /*
    |--------------------------------------------------------------------------
    | IT team inbox
    |--------------------------------------------------------------------------
    | One shared address for the whole IT team (not tied to any user account, so it
    | can be used even though every account needs its own email). When set:
    |   - every NEW TICKET alert is also sent here, so the team has one place to watch;
    |   - replies to system emails come back here (Reply-To);
    |   - individual IT accounts no longer get a personal new-ticket email by default
    |     (they still see it in the bell, and can switch the email on in their Profile).
    | The email the system SENDS FROM is separate: MAIL_FROM_ADDRESS.
    | Tip: in cPanel > Forwarders, forward this mailbox to each IT person's own email.
    */
    'team_inbox' => env('IT_TEAM_EMAIL'),

];
