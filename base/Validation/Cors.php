<?php

namespace Base\Validation;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request)
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods','POST')
            ->header('Access-Control-Allow-Headers',
                'Accept, Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN');
    }
}
