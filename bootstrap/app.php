<?php

use App\Http\Middleware\EnsureActiveStudentSubscription;
use App\Http\Middleware\ResolveStudentMess;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [SetLocale::class]);

        // SSLCommerz posts back to these URLs from its own servers, without a
        // browser session or CSRF token.
        $middleware->validateCsrfTokens(except: [
            'payments/sslcommerz/*',
        ]);

        $middleware->alias([
            'student.subscription' => EnsureActiveStudentSubscription::class,
            'student.mess' => ResolveStudentMess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
