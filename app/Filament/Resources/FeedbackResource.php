<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToAuthCompany;
use App\Filament\Resources\FeedbackResource\Pages;
use App\Models\Feedback;
use Filament\Forms;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section as SchemaSection;

class FeedbackResource extends Resource
{
    use ScopedToAuthCompany;

    protected static ?string $model = Feedback::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Communication';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Feedback';
    protected static ?int $navigationSort = 3;

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?\Illuminate\Database\Eloquent\Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return static::getUrl('create', $parameters, $isAbsolute, $panel, $tenant, $shouldGuessMissingParameters);
    }

    public static function getNavigationItems(): array
    {
        return [
            NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon())
                ->sort(static::getNavigationSort())
                ->isActiveWhen(fn (): bool => request()->routeIs(static::getRouteBaseName() . '.*'))
                ->url(static::getUrl('create')),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaSection::make()->schema([
                Forms\Components\Select::make('type')
                    ->options([
                        'general'         => 'General',
                        'bug'             => 'Bug / Issue',
                        'feature_request' => 'Feature Request',
                        'complaint'       => 'Complaint',
                        'suggestion'      => 'Suggestion',
                    ])
                    ->default('general')
                    ->required(),
                Forms\Components\Select::make('rating')
                    ->label('Rating (optional)')
                    ->options([
                        1 => '★☆☆☆☆  1 — Poor',
                        2 => '★★☆☆☆  2 — Fair',
                        3 => '★★★☆☆  3 — Good',
                        4 => '★★★★☆  4 — Very Good',
                        5 => '★★★★★  5 — Excellent',
                    ])
                    ->nullable(),
                Forms\Components\TextInput::make('subject')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('source')->default('admin'),
                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => \Illuminate\Support\Facades\Auth::id()),
                Forms\Components\Hidden::make('company_id')
                    ->default(function () {
                        $user = \Illuminate\Support\Facades\Auth::user();
                        return $user instanceof \App\Models\User ? $user->company_id : null;
                    }),
            ])->columns(2),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'create' => Pages\CreateFeedback::route('/create'),
        ];
    }
}
