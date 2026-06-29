<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
        ResetPassword::createUrlUsing(function ($user, string $token) {
            return config('app.frontend_url') . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        });

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return [
                Limit::perMinute(5)->by('login|' . $email . '|' . $request->ip()),
                Limit::perMinute(20)->by('login|ip|' . $request->ip()),
            ];
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            $email = (string) $request->input('email');
            return [
                Limit::perMinute(5)->by('forgot|' . $email . '|' . $request->ip()),
                Limit::perMinute(20)->by('forgot|ip|' . $request->ip()),
            ];
        });

        RateLimiter::for('reset-password', function (Request $request) {
            return [
                Limit::perMinute(5)->by('reset|ip|' . $request->ip()),
            ];
        });
    }
}
