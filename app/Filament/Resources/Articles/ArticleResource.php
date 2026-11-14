<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Resources\Articles\Pages\ManageArticles;
use App\Models\Article;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255)
                ->rule('regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                ->helperText('Lowercase letters, numbers and single hyphens only. Used in the public URL.'),
            Textarea::make('summary')->required()->columnSpanFull(),
            FileUpload::make('cover_path')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(4096)
                ->imagePreviewHeight('160')
                ->disk('public')
                ->directory('articles')
                ->helperText('JPEG, PNG or WebP; up to 4 MB.'),
            DateTimePicker::make('published_at'),
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
                ImageColumn::make('cover_path')->label('Cover')->disk('public')->square(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('publication_status')
                    ->label('Public status')
                    ->getStateUsing(fn (Article $record): string => $record->publicationStatus())
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Live' => 'success',
                        'Scheduled' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('published_at')->label('Scheduled at')->dateTime()->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([TernaryFilter::make('is_published')])
            ->recordActions([
                EditAction::make(),
                Action::make('preview')->label('Preview saved')->url(fn (Article $record): string => route('articles.preview', $record))->openUrlInNewTab(),
                Action::make('viewPublic')
                    ->label('View public')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Article $record): string => route('articles.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (Article $record): bool => $record->publicationStatus() === 'Live'),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageArticles::route('/')];
    }
}
