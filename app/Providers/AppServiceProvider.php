<?php

namespace App\Providers;

use App\Http\Middleware\EnsureMfaIsEnabled;
use App\Http\Middleware\RedirectIfPageHidden;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Middleware\RoleMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureDisabledAccountLogout();

        Livewire::addPersistentMiddleware([
            RedirectIfPageHidden::class,
            RoleMiddleware::class,
            EnsureMfaIsEnabled::class,
        ]);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * A disabled account can still complete credential/2FA/passkey checks —
     * this catches it right after, at the one point every login path
     * converges, and tears the session back down immediately.
     */
    protected function configureDisabledAccountLogout(): void
    {
        Event::listen(function (Login $event): void {
            if (! $event->user instanceof User || ! $event->user->isDisabled()) {
                return;
            }

            Auth::guard($event->guard)->logout();

            $request = request();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('This account has been disabled.'),
            ]);
        });
    }
}
