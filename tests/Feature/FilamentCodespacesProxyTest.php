<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentCodespacesProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_codespaces_admin_intended_url_is_https_even_without_forwarded_headers(): void
    {
        config()->set('app.env', 'local');
        config()->set('app.url', 'https://sample-8080.app.github.dev');

        $this->withServerVariables([
            'REMOTE_ADDR' => '172.18.0.5',
            'HTTP_HOST' => 'localhost:8080',
        ]);

        $this->get('/admin')
            ->assertRedirect('https://sample-8080.app.github.dev/admin/login');

        $this->assertSame(
            'https://sample-8080.app.github.dev/admin',
            session('url.intended'),
        );
    }

    public function test_admin_login_redirect_and_intended_destination_use_forwarded_codespaces_https_host(): void
    {
        config()->set('app.env', 'local');
        config()->set('app.url', 'https://sample-8080.app.github.dev');
        (new \App\Providers\AppServiceProvider($this->app))->boot();

        $this->withServerVariables([
            'REMOTE_ADDR' => '172.18.0.5',
            'HTTP_HOST' => 'localhost:8080',
            'HTTP_X_FORWARDED_HOST' => 'sample-8080.app.github.dev',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT' => '443',
        ]);

        $this->get('/admin')
            ->assertRedirect('https://sample-8080.app.github.dev/admin/login');

        $this->assertSame(
            'https://sample-8080.app.github.dev/admin',
            session('url.intended'),
        );

        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee('http://localhost:8080/admin', false);
    }
}
