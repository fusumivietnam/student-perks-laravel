<?php

namespace App\Providers;

use App\Models\Page;
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
        View::composer('layouts.public', function ($view): void {
            $view->with('footerPages', Page::published()
                ->whereIn('slug', ['about', 'privacy', 'terms', 'contact'])
                ->pluck('slug'));
        });
    }
}
