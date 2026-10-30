<?php

namespace Torob\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class CaptureTorobClid
{
    public const COOKIE_NAME = 'torob_clid';
    public const COOKIE_LIFETIME_MINUTES = 10080; // 7 days (168 hours)

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $clid = $request->query(self::COOKIE_NAME);
        if ($clid !== null && is_string($clid) && $clid !== '') {
            $cookie = new Cookie(
                name: self::COOKIE_NAME,
                value: $clid,
                expire: time() + (self::COOKIE_LIFETIME_MINUTES * 60),
                path: '/',
                domain: null,
                secure: $request->isSecure(),
                httpOnly: true,
                raw: false,
                sameSite: Cookie::SAMESITE_LAX
            );

            $response->headers->setCookie($cookie);
        }

        return $response;
    }
}
