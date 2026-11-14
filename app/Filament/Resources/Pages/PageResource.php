<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\ManagePages;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255)
                ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->helperText('Lowercase letters, numbers and single hyphens only. Used in the public URL.'),
            Textarea::make('body')->required()->columnSpanFull(),
            Toggle::make('is_published')->default(false),
            TextInput::make('seo_title')->maxLength(255),
            Textarea::make('seo_description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('publication_status')
                    ->label('Public status')
                    ->getStateUsing(fn (Page $record): string => $record->publicationStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Live' => 'success',
                        'Scheduled' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_published')])
            ->recordActions([
                EditAction::make(),
                Action::make('preview')->label('Preview saved')->url(fn (Page $record): string => route('pages.preview', $record))->openUrlInNewTab(),
                Action::make('viewPublic')
                    ->label('View public')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Page $record): string => route('pages.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (Page $record): bool => $record->publicationStatus() === 'Live'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePages::route('/')];
    }
}
