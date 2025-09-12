<?php

namespace Base\Component;

use Base\Auth\Authenticate;
use Base\Validation\RequestValidation;

class BaseMiddleware
{
    private string $type;
    public function __construct($type) { $this->type = $type; }
    public function getMiddlewares() : array {
        //TODO : Option to add on DB
        $apiGroup = [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ];
        $webGroup = [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ];
        $aliasGroup = [
            'auth' => Authenticate::class,
            'validation' => RequestValidation::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        ];

        if($this->type === 'api') return $apiGroup;
        else if($this->type === 'web') return $webGroup;
        else if($this->type === 'alias') return $aliasGroup;
        else return [];
    }
}
