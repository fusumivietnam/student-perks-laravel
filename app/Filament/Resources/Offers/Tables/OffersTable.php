<?php

namespace App\Filament\Resources\Offers\Tables;

use App\Models\Offer;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('brand.name')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('offer_type')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('offer_value')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('partner_url')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('partner_cta_status')
                    ->label('Partner CTA')
                    ->getStateUsing(fn (Offer $record): string => $record->partnerCtaStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Ready' => 'success',
                        'Review due' => 'warning',
                        default => 'danger',
                    }),
                TextColumn::make('partner_reviewed_at')
                    ->label('Link last reviewed')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never reviewed')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('publication_status')
                    ->label('Public status')
                    ->getStateUsing(fn (Offer $record): string => $record->publicationStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Live' => 'success',
                        'Scheduled' => 'warning',
                        'Expired' => 'danger',
                        'Hidden by brand/category' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('editorial_review')
                    ->label('Needs details')
                    ->getStateUsing(fn (Offer $record): bool => $record->hasMissingEditorialDetails())
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('success'),
                IconColumn::make('is_published')
                    ->boolean()
                    ->label('Enabled')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('seo_title')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('brand_id')->relationship('brand', 'name')->searchable(),
                SelectFilter::make('category_id')->relationship('category', 'name')->searchable(),
                TernaryFilter::make('is_published'),
                SelectFilter::make('publication_status')
                    ->label('Public status')
                    ->options([
                        'draft' => 'Draft',
                        'live' => 'Live',
                        'scheduled' => 'Scheduled',
                        'expired' => 'Expired',
                        'hidden' => 'Hidden by brand/category',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->withPublicationStatus($data['value'] ?? '')),
                Filter::make('expiring_soon')
                    ->label('Expiring within 7 days')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon()),
                Filter::make('needs_editorial_review')
                    ->label('Missing eligibility or redemption steps')
                    ->query(fn (Builder $query): Builder => $query->needsEditorialReview()),
                Filter::make('partner_review_due')
                    ->label('Live links not reviewed in 90 days')
                    ->query(fn (Builder $query): Builder => $query->partnerReviewDue()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Offer $record): string => route('offers.preview', ['offer' => $record->getKey()]))
                    ->openUrlInNewTab(),
                Action::make('viewLive')
                    ->label('View public')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Offer $record): string => route('offers.show', ['slug' => $record->slug]))
                    ->openUrlInNewTab()
                    ->visible(fn (Offer $record): bool => $record->publicationStatus() === 'Live'),
                Action::make('confirmPartnerReview')
                    ->label('Mark link reviewed')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm manual partner-link review')
                    ->modalDescription('Only confirm after independently opening the official partner URL and checking its terms. This unlocks the public partner button for 90 days. Changing the URL revokes the review. No automated HTTP health check is performed.')
                    ->visible(fn (Offer $record): bool => $record->hasValidPartnerDestination() && $record->needsPartnerReview())
                    ->action(function (Offer $record): void {
                        $record->refresh();

                        if (! $record->hasValidPartnerDestination() || ! $record->needsPartnerReview()) {
                            return;
                        }

                        $record->forceFill(['partner_reviewed_at' => now()])->save();
                    }),
                Action::make('publish')
                    ->label('Publish now')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Offer $record): bool => $record->canPublishNow())
                    ->action(function (Offer $record): void {
                        $record->refresh();

                        if (! $record->canPublishNow()) {
                            return;
                        }

                        $record->update(['is_published' => true]);
                    }),
                Action::make('unpublish')
                    ->label('Unpublish')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Offer $record): bool => (bool) $record->is_published)
                    ->action(function (Offer $record): void {
                        $record->refresh();

                        if ($record->is_published) {
                            $record->update(['is_published' => false]);
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('unpublishSelected')
                        ->label('Unpublish selected')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records): void {
                            $records->each(function (Offer $record): void {
                                if ($record->is_published) {
                                    $record->update(['is_published' => false]);
                                }
                            });
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
