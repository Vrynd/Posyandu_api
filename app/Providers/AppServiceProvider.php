<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Rate Limiter: Login (5 attempts per minute per IP)
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 1 menit.',
                ], 429);
            });
        });

        // Rate Limiter: Register (3 attempts per minute per IP)
        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan registrasi. Silakan coba lagi dalam 1 menit.',
                ], 429);
            });
        });
    }
}
