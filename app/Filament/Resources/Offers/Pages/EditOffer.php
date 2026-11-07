<?php

namespace App\Filament\Resources\Offers\Pages;

use App\Filament\Resources\Offers\OfferResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview saved offer')
                ->url(fn (): string => route('offers.preview', ['offer' => $this->getRecord()->getKey()]))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
