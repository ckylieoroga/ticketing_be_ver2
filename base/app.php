<?php

use Base\Validation\Cors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            Route::middleware('api')
                ->prefix('gateway')
                ->group(base_path('base/Routes/api.php'));

            Route::middleware('web')
                ->group(base_path('base/Routes/web.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(Cors::class);
        $middleware->alias((new \Base\Component\BaseMiddleware('alias'))->getMiddlewares());
        $middleware->group('api', (new \Base\Component\BaseMiddleware('api'))->getMiddlewares());
        $middleware->group('web', (new \Base\Component\BaseMiddleware('web'))->getMiddlewares());
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
