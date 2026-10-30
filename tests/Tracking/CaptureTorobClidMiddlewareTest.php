<?php

namespace Torob\Laravel\Tests\Tracking;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Torob\Laravel\Facades\Torob;
use Torob\Laravel\Http\Middleware\CaptureTorobClid;
use Torob\Laravel\Tests\TestCase;

class CaptureTorobClidMiddlewareTest extends TestCase
{
    public function test_attaches_cookie_when_torob_clid_present_in_query(): void
    {
        $middleware = new CaptureTorobClid();
        $request = Request::create('http://localhost/product/123?torob_clid=test-click-id-123', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('ok');
        });

        $cookies = $response->headers->getCookies();
        $this->assertNotEmpty($cookies);

        $torobCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'torob_clid') {
                $torobCookie = $cookie;
                break;
            }
        }

        $this->assertNotNull($torobCookie);
        $this->assertSame('test-click-id-123', $torobCookie->getValue());
        // 7 days = 168 hours = 604800 seconds
        $this->assertGreaterThan(time() + 600000, $torobCookie->getExpiresTime());
    }

    public function test_does_not_attach_cookie_when_torob_clid_absent(): void
    {
        $middleware = new CaptureTorobClid();
        $request = Request::create('http://localhost/product/123', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return new Response('ok');
        });

        $cookies = $response->headers->getCookies();
        $hasTorob = false;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'torob_clid') {
                $hasTorob = true;
            }
        }

        $this->assertFalse($hasTorob);
    }

    public function test_helper_retrieves_clid_from_query_or_cookie(): void
    {
        $requestWithQuery = Request::create('http://localhost/checkout?torob_clid=query-clid', 'GET');
        $this->assertSame('query-clid', Torob::getClid($requestWithQuery));

        $requestWithCookie = Request::create('http://localhost/checkout', 'GET');
        $requestWithCookie->cookies->set('torob_clid', 'cookie-clid');
        $this->assertSame('cookie-clid', Torob::getClid($requestWithCookie));

        $requestEmpty = Request::create('http://localhost/checkout', 'GET');
        $this->assertNull(Torob::getClid($requestEmpty));
    }
}
