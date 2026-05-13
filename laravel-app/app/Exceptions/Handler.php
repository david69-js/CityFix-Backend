<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types that should not be reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        // Intentionally empty — we want to log everything for diagnostics.
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * Logs every exception with its full context (message, file, line, and
     * stack trace) to both the default Laravel log channel and to stderr so
     * the output is always visible in Railway's log stream.
     */
    public function report(Throwable $e): void
    {
        $context = [
            'exception' => get_class($e),
            'message'   => $e->getMessage(),
            'file'      => $e->getFile(),
            'line'      => $e->getLine(),
            'trace'     => $e->getTraceAsString(),
        ];

        // Always write to stderr so Railway captures it regardless of the
        // configured log channel or storage permissions.
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

        // Also delegate to Laravel's built-in reporter (writes to the
        // configured log channel, e.g. storage/logs/laravel.log).
        try {
            \Illuminate\Support\Facades\Log::channel('stderr')->error(
                '[CityFix] Unhandled exception: ' . $e->getMessage(),
                $context
            );
        } catch (Throwable) {
            // If the logger itself fails, the stderr write above is our safety net.
        }

        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * - In debug mode: returns the full exception details in the JSON body.
     * - In production: returns a user-friendly message while still logging
     *   the full details via report().
     */
    public function render($request, Throwable $e): JsonResponse|\Symfony\Component\HttpFoundation\Response
    {
        // Always report so the full trace is logged before we shape the response.
        $this->report($e);

        // ── Validation errors ────────────────────────────────────────────────
        if ($e instanceof ValidationException) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => $e->errors(),
            ], 422);
        }

        // ── Authentication errors ────────────────────────────────────────────
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // ── Model not found / route model binding ────────────────────────────
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return response()->json([
                'message' => 'Resource not found.',
            ], 404);
        }

        // ── Named HTTP exceptions (403, 429, etc.) ───────────────────────────
        if ($e instanceof HttpException) {
            return response()->json([
                'message' => $e->getMessage() ?: 'HTTP error.',
            ], $e->getStatusCode());
        }

        // ── Everything else (the 500s we actually care about) ────────────────
        $statusCode = 500;

        if (config('app.debug')) {
            // Full details in debug / development mode.
            return response()->json([
                'message'   => $e->getMessage(),
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => collect(explode("\n", $e->getTraceAsString()))
                    ->filter()
                    ->values()
                    ->all(),
            ], $statusCode);
        }

        // Production: safe message only — full details are in the logs.
        return response()->json([
            'message' => 'Server Error. Our team has been notified.',
        ], $statusCode);
    }
}
