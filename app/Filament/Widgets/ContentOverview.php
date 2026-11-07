<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Offer;
use App\Models\Page;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $publishedOffers = Offer::current()->count();
        $totalOffers = Offer::query()->count();

        return [
            Stat::make('Live offers', $publishedOffers)
                ->description('Currently visible in the public directory')
                ->color('success'),
            Stat::make('Offers not live', $totalOffers - $publishedOffers)
                ->description('Draft, scheduled, expired, or hidden by taxonomy')
                ->color('warning'),
            Stat::make('Published brands', Brand::query()->where('is_published', true)->count())
                ->description('Visible brand profiles'),
            Stat::make('Published categories', Category::query()->where('is_published', true)->count())
                ->description('Visible category profiles'),
            Stat::make('Live articles', Article::published()->count())
                ->description('Currently visible guides'),
            Stat::make('Published pages', Page::published()->count())
                ->description('Visible information and policy pages'),
        ];
    }
}
