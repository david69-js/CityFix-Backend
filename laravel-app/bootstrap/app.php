<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Ensure all API requests return JSON on error instead of redirecting
        $middleware->redirectTo(
            guests: function (Request $request) {
                if ($request->is('api/*')) {
                    return null;
                }
                return route('login');
            }
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Always render JSON for API routes.
        $exceptions->shouldRenderJsonWhen(function (Request $request, \Throwable $e) {
            if ($request->is('api/*')) {
                return true;
            }

            return $request->expectsJson();
        });

        // Log every exception with full context to stderr so Railway captures
        // it regardless of storage/log configuration.
        $exceptions->report(function (\Throwable $e) {
            $context = [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ];

            // Write directly to stderr — always visible in Railway log stream.
            $stderr = fopen('php://stderr', 'w');
            if ($stderr !== false) {
                fwrite(
                    $stderr,
                    '[CityFix Exception] ' . get_class($e) . ': ' . $e->getMessage()
                        . ' in ' . $e->getFile() . ':' . $e->getLine()
                        . PHP_EOL . $e->getTraceAsString() . PHP_EOL
                );
                fclose($stderr);
            }

            // Also push through Laravel's logger (stderr channel so it surfaces
            // in Railway even when the default channel is 'single').
            try {
                \Illuminate\Support\Facades\Log::channel('stderr')->error(
                    '[CityFix] Unhandled exception: ' . $e->getMessage(),
                    $context
                );
            } catch (\Throwable) {
                // If the logger itself fails, the raw stderr write above is the
                // safety net — do not swallow the original exception.
            }

            // Return false to let Laravel's default reporter also run (writes
            // to storage/logs/laravel.log in addition to the above).
            return false;
        });

        // Shape 500 responses: full details in debug mode, safe message in prod.
        $exceptions->render(function (\Throwable $e, Request $request) {
            // Only intercept unhandled server errors on API routes.
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null; // Let Laravel handle non-API responses normally.
            }

            // Let Laravel's built-in handlers deal with HTTP + validation
            // exceptions — they already produce well-formed JSON responses.
            if (
                $e instanceof \Illuminate\Validation\ValidationException
                || $e instanceof \Illuminate\Auth\AuthenticationException
                || $e instanceof \Symfony\Component\HttpKernel\Exception\HttpException
                || $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
            ) {
                return null;
            }

            // Unhandled exceptions (the real 500s).
            if (config('app.debug')) {
                return response()->json([
                    'message'   => $e->getMessage(),
                    'exception' => get_class($e),
                    'file'      => $e->getFile(),
                    'line'      => $e->getLine(),
                    'trace'     => collect(explode("\n", $e->getTraceAsString()))
                        ->filter()
                        ->values()
                        ->all(),
                ], 500);
            }

            return response()->json([
                'message' => 'Server Error. Our team has been notified.',
            ], 500);
        });
    })
    ->create();
