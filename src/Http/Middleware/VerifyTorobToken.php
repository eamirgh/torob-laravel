<?php

namespace Torob\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Torob\Laravel\Exceptions\TorobTokenException;
use Torob\Laravel\Security\TorobTokenVerifier;

class VerifyTorobToken
{
    public function __construct(
        protected TorobTokenVerifier $verifier
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Torob-Token');
        if (empty($token)) {
            return new JsonResponse(['error' => 'Missing X-Torob-Token header'], 401);
        }

        $version = $request->header('X-Torob-Token-Version');
        if ($version !== null && (string) $version !== '1') {
            return new JsonResponse(['error' => "Unsupported token version: {$version}"], 401);
        }

        try {
            // Audience corresponds to Host header
            $host = $request->getHost();
            if ($request->getPort() && !in_array($request->getPort(), [80, 443])) {
                $hostWithPort = "{$host}:{$request->getPort()}";
            } else {
                $hostWithPort = $host;
            }

            // Verify with host, or try hostWithPort if different
            try {
                $payload = $this->verifier->verify($token, $hostWithPort);
            } catch (TorobTokenException $e) {
                if ($hostWithPort !== $host) {
                    $payload = $this->verifier->verify($token, $host);
                } else {
                    throw $e;
                }
            }

            $request->attributes->set('torob_token', $payload);
        } catch (TorobTokenException $e) {
            return new JsonResponse(['error' => 'Unauthorized: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}
