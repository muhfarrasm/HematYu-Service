<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    // ... config default kamu

    public function render($request, Throwable $exception)
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], $this->isHttpException($exception) ? $exception->getStatusCode() : 500);
        }

        return parent::render($request, $exception);
    }
}
