<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductionTrustedProxyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TrustProxies::flushState();

        parent::tearDown();
    }

    public function test_untrusted_client_cannot_spoof_https_or_host(): void
    {
        TrustProxies::at(['10.42.0.10']);
        TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);

        $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.9',
            'HTTP_HOST' => 'example.test',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_HOST' => 'attacker.invalid',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('href="http://example.test/offers"', false)
            ->assertDontSee('attacker.invalid');
    }

    public function test_approved_proxy_can_supply_https_but_not_forwarded_host(): void
    {
        TrustProxies::at(['10.42.0.10']);
        TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);

        $this->withServerVariables([
            'REMOTE_ADDR' => '10.42.0.10',
            'HTTP_HOST' => 'student.example.test',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
            'HTTP_X_FORWARDED_HOST' => 'attacker.invalid',
        ]);

        $this->get('/')->assertOk()
            ->assertSee('href="https://student.example.test/offers"', false)
            ->assertDontSee('attacker.invalid');
    }
}
