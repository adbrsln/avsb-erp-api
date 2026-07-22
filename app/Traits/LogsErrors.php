<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait LogsErrors
{
    /**
     * Log an error with request context and return a JSON error response.
     */
    protected function logError(\Throwable $e, ?Request $request = null, array $extra = []): void
    {
        $context = array_merge([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ], $extra);

        if ($request) {
            $context['url'] = $request->fullUrl();
            $context['method'] = $request->method();
            $context['user_id'] = $request->user()?->id;
            $context['ip'] = $request->ip();
        }

        // Determine the calling controller method for context
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        if (isset($trace[1]['function'])) {
            $context['controller_method'] = $trace[1]['function'];
        }

        Log::error($e->getMessage(), $context);
    }

    /**
     * Log a caught exception and return a standardized error JSON response.
     */
    protected function errorResponse(\Throwable $e, ?Request $request = null, string $message = 'An error occurred', int $status = 500, array $extra = []): JsonResponse
    {
        $this->logError($e, $request, $extra);

        return response()->json(['error' => $message], $status);
    }
}
