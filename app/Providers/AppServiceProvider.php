<?php

namespace App\Providers;

use App\Models\Page;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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
                ->whereIn('slug', ['about', 'privacy', 'terms', 'contact', 'disclaimer'])
                ->pluck('slug'));
        });
    }
}
