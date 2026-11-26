<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Offers\OfferResource;
use App\Filament\Resources\Pages\PageResource;
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

        $reviewDue = Offer::partnerReviewDue()->count();
        $missingDetails = Offer::needsEditorialReview()->count();
        $expiringSoon = Offer::expiringSoon()->count();
        $requiredPages = ['about', 'privacy', 'terms', 'contact', 'disclaimer'];
        $readyPages = Page::published()->whereIn('slug', $requiredPages)->whereRaw("TRIM(body) <> ''")->count();
        $missingPages = count($requiredPages) - $readyPages;

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
            Stat::make('Partner reviews due', $reviewDue)
                ->description('Live offers needing manual partner review')
                ->color($reviewDue > 0 ? 'warning' : 'success')
                ->url(OfferResource::getUrl('index', ['tableFilters' => ['partner_review_due' => ['isActive' => true]]], panel: 'admin')),
            Stat::make('Offers needing details', $missingDetails)
                ->description('Missing eligibility or redemption steps, including drafts')
                ->color($missingDetails > 0 ? 'warning' : 'success')
                ->url(OfferResource::getUrl('index', ['tableFilters' => ['needs_editorial_review' => ['isActive' => true]]], panel: 'admin')),
            Stat::make('Expiring within 7 days', $expiringSoon)
                ->description('Current offers approaching their end date')
                ->color($expiringSoon > 0 ? 'warning' : 'success')
                ->url(OfferResource::getUrl('index', ['tableFilters' => ['expiring_soon' => ['isActive' => true]]], panel: 'admin')),
            Stat::make('Required pages not ready', $missingPages)
                ->description('Missing, unpublished or empty required pages; not a launch approval')
                ->color($missingPages > 0 ? 'warning' : 'success')
                ->url(PageResource::getUrl('index', panel: 'admin')),
        ];
    }
}
