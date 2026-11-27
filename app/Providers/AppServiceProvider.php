<?php

namespace App\Providers;

use App\Models\Page;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Configuration is available here, unlike early kernel resolution.
        TrustProxies::at(config('security.trusted_proxies', []));
        TrustProxies::withHeaders(Request::HEADER_X_FORWARDED_PROTO | Request::HEADER_X_FORWARDED_PORT);

        // Codespaces terminates HTTPS before forwarding HTTP to the private preview.
        // Only force the known Codespaces origin; ordinary hosts keep request-based URLs.
        $previewUrl = (string) config('app.url');
        $previewHost = parse_url($previewUrl, PHP_URL_HOST);
        if (config('app.env') === 'local'
            && parse_url($previewUrl, PHP_URL_SCHEME) === 'https'
            && is_string($previewHost)
            && str_ends_with($previewHost, '.app.github.dev')) {
            URL::useOrigin($previewUrl);
            URL::useAssetOrigin($previewUrl);
            URL::forceScheme('https');
        }

        View::composer('layouts.public', function ($view): void {
            $view->with('footerPages', Page::published()
                ->whereIn('slug', array_keys(config('launch-pages', [])))
                ->pluck('slug'));
        });
    }
}
