<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfPageHidden
{
    /**
     * Send a user whose role carries the given restriction back to their own
     * landing page instead of the page it hides.
     *
     * A redirect rather than a 403 keeps the post-login `/dashboard` hop
     * working for roles that cannot see the dashboard.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $restriction): Response
    {
        $user = $request->user();

        if ($user && $user->isPageHidden($restriction)) {
            return redirect()->route($user->landingRoute());
        }

        return $next($request);
    }
}
