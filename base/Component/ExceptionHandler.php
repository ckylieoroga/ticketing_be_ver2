<?php

namespace Base\Component;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandlers;
use Throwable;

class ExceptionHandler extends ExceptionHandlers
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()){
            $requestParam = $request->all();
            $errorLog['request'] = $requestParam['request'] ?? null;
            $errorLog['type'] = $requestParam['type'] ?? null;
            $errorLog = ["response" => "Access denied!", "request" => $errorLog];
            return returnResponse($errorLog, 401);
        }
        return parent::render($request, $exception); // TODO: Change the auto generated stub
    }

}
