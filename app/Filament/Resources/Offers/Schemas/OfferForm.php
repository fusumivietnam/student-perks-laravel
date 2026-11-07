<?php

namespace App\Filament\Resources\Offers\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class OfferForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('brand_id')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->unique(ignoreRecord: true)
                    ->rule('unique:offer_slug_redirects,slug')
                    ->helperText('Lowercase letters, numbers and hyphens only. Previously published URLs are reserved for redirects.'),
                Textarea::make('summary')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('offer_type')
                    ->required()
                    ->maxLength(255)
                    ->default('discount'),
                TextInput::make('offer_value')
                    ->maxLength(255),
                Textarea::make('eligibility')
                    ->columnSpanFull(),
                Textarea::make('redemption_steps')
                    ->columnSpanFull(),
                TextInput::make('partner_url')
                    ->maxLength(2048)
                    ->url()
                    ->rule('starts_with:https://')
                    ->required(),
                Toggle::make('is_published')
                    ->default(false)
                    ->helperText('Only visible when enabled, its brand and category are published, and all scheduled dates permit it.'),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at')
                    ->after('starts_at'),
                DateTimePicker::make('published_at')
                    ->helperText('Optional: keep the offer hidden until this time, even when Published is enabled.'),
                TextInput::make('seo_title')
                    ->maxLength(255),
                Textarea::make('seo_description')
                    ->columnSpanFull(),
            ]);
    }
}
