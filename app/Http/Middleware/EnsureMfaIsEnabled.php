<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMfaIsEnabled
{
    /**
     * Send an admin without two-factor authentication or a passkey on file
     * to the security settings page to set one up, instead of letting them
     * into the route this middleware guards.
     *
     * Registered as Livewire persistent middleware (see AppServiceProvider),
     * so it runs on every request regardless of which route/page it's
     * attached to — hence the explicit admin-role check rather than relying
     * on `role:admin` route middleware alone.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('admin') && ! $user->hasEnabledTwoFactorAuthentication() && ! $user->hasPasskeysEnabled()) {
            return redirect()->route('security.edit')->with('status', 'mfa-required');
        }

        return $next($request);
    }
}
