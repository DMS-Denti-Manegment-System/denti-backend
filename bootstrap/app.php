<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: null,
        health: null,
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->statefulApi();
        $middleware->append(\App\Http\Middleware\RequestContextMiddleware::class);

        /* $middleware->api(append: [
            \App\Http\Middleware\EnsureTwoFactorIsVerified::class,
        ]); */

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            '2fa.verified' => \App\Http\Middleware\EnsureTwoFactorIsVerified::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        $schedule->job(new \App\Jobs\CheckAllStockLevelsJob)->dailyAt('08:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'data' => null,
                'errors' => $e->errors(),
                'meta' => null,
            ], 422);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 401);
        });

        $exceptions->render(function (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 403);
        });

        $exceptions->render(function (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 404);
        });

        $exceptions->render(function (ThrottleRequestsException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Too many requests.',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 429);
        });

        $exceptions->render(function (\App\Exceptions\Stock\StockNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 404);
        });

        $exceptions->render(function (\App\Exceptions\Stock\InsufficientStockException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 400);
        });

        $exceptions->render(function (\Throwable $e) {
            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'HTTP error.',
                    'data' => null,
                    'errors' => null,
                    'meta' => null,
                ], $e->getStatusCode());
            }

            return response()->json([
                'success' => false,
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error.',
                'data' => null,
                'errors' => null,
                'meta' => null,
            ], 500);
        });
    })->create();
