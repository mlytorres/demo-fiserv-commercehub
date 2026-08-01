<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Demo access gate
    |--------------------------------------------------------------------------
    |
    | This is a single shared team login gating the staff-facing pages of the
    | Fiserv Commerce Hub sandbox demo (charge/void/refund harness, invoices
    | admin, transaction history, webhook logs) — not a real user system, no
    | registration, no per-person accounts. Set these in .env and share them
    | with your team out of band. The customer-facing /pay/{invoice} payment
    | links are intentionally NOT behind this gate — see routes/web.php.
    |
    */

    'username' => env('DEMO_AUTH_USERNAME'),

    'password' => env('DEMO_AUTH_PASSWORD'),

];
