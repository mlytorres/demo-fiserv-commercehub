<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates the staff-facing pages of the demo behind a single shared team
 * login (see config/demo_auth.php) — not a real user system, just enough
 * to keep this sandbox off the open internet. The customer-facing
 * /pay/{invoice} payment links are intentionally excluded from this
 * middleware in routes/web.php.
 */
class EnsureDemoAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('demo_authenticated')) {
            return redirect()->guest(route('fiserv.demo.login'));
        }

        return $next($request);
    }
}
