<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToAuthCompany;
use App\Filament\Resources\NewsFeedResource\Pages;
use App\Models\NewsFeed;
use Filament\Forms;

use Filament\Infolists;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components as SchemaComponents;

class NewsFeedResource extends Resource
{
    use ScopedToAuthCompany;

    protected static ?string $model = NewsFeed::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Communication';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationLabel = 'Newsfeed';
    protected static ?string $modelLabel = 'Announcement';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make()->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('body')
                    ->required()
                    ->toolbarButtons([
                        'bold', 'italic', 'underline', 'strike',
                        'bulletList', 'orderedList', 'blockquote',
                        'link', 'h2', 'h3',
                    ])
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options([
                        'announcement' => 'Announcement',
                        'news' => 'News',
                        'alert' => 'Alert',
                    ])
                    ->default('announcement')
                    ->required(),
                Forms\Components\Hidden::make('author_id')
                    ->default(fn() => Auth::id()),
                Forms\Components\Toggle::make('pinned')
                    ->label('Pin to top')
                    ->default(false),
                Forms\Components\Toggle::make('requires_confirmation')
                    ->label('Require read confirmation')
                    ->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('pinned')
                    ->icon(fn(bool $state): string => $state ? 'heroicon-s-bookmark' : 'heroicon-o-bookmark')
                    ->color(fn(bool $state): string => $state ? 'warning' : 'gray')
                    ->label(''),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('semibold')
                    ->limit(60),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'alert' => 'danger',
                        'announcement' => 'indigo',
                        'news' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('author.name')
                    ->label('Author'),
                Tables\Columns\IconColumn::make('requires_confirmation')
                    ->label('Confirm Required')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Published')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'announcement' => 'Announcement',
                        'news' => 'News',
                        'alert' => 'Alert',
                    ]),
                Tables\Filters\TernaryFilter::make('pinned')
                    ->label('Pinned'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                TableAction::make('toggle_pin')
                    ->label(fn(NewsFeed $record): string => $record->pinned ? 'Unpin' : 'Pin')
                    ->icon(fn(NewsFeed $record): string => $record->pinned ? 'heroicon-o-bookmark-slash' : 'heroicon-o-bookmark')
                    ->action(fn(NewsFeed $record) => $record->update(['pinned' => !$record->pinned])),
                DeleteAction::make(),
            ])
            ->reorderable('id')
            ->defaultSort('pinned', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaSection::make()->schema([
                Infolists\Components\TextEntry::make('title')
                    ->hiddenLabel()
                    ->size('lg')
                    ->weight('bold')
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('type')->badge()
                    ->color(fn($state) => match($state) {
                        'alert' => 'danger', 'announcement' => 'indigo', 'news' => 'success', default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('author.name')->label('Author'),
                Infolists\Components\TextEntry::make('created_at')->label('Published')->dateTime(),
                Infolists\Components\IconEntry::make('pinned')->boolean(),
                Infolists\Components\IconEntry::make('requires_confirmation')->label('Confirm Required')->boolean(),
                Infolists\Components\TextEntry::make('body')
                    ->html()
                    ->hiddenLabel()
                    ->columnSpanFull(),
            ])->columns(4),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsFeeds::route('/'),
            'create' => Pages\CreateNewsFeed::route('/create'),
            'view' => Pages\ViewNewsFeed::route('/{record}'),
            'edit' => Pages\EditNewsFeed::route('/{record}/edit'),
        ];
    }
}
