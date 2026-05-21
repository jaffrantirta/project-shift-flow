<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\TenantResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;

class TenantResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Tenants';
    protected static ?string $modelLabel = 'Tenant';
    protected static ?string $pluralModelLabel = 'Tenants';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Company Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->alphaDash(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(30),
                Forms\Components\Textarea::make('address')
                    ->rows(2)
                    ->maxLength(500),
                Forms\Components\Select::make('timezone')
                    ->options(collect(timezone_identifiers_list())->mapWithKeys(fn($tz) => [$tz => $tz]))
                    ->searchable()
                    ->default('UTC')
                    ->required(),
                Forms\Components\FileUpload::make('logo')
                    ->image()
                    ->directory('company-logos')
                    ->imageEditor()
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->square()
                    ->size(40)
                    ->defaultImageUrl(fn(Company $r): string => 'https://ui-avatars.com/api/?name=' . urlencode($r->name) . '&color=6366f1&background=e0e7ff'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('slug')
                    ->copyable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('timezone')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Registered')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_this_month')
                    ->label('New This Month')
                    ->query(fn($query) => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Company Details')->schema([
                Infolists\Components\ImageEntry::make('logo')
                    ->square()
                    ->size(80)
                    ->defaultImageUrl(fn(Company $r): string => 'https://ui-avatars.com/api/?name=' . urlencode($r->name) . '&size=80&color=6366f1&background=e0e7ff'),
                Infolists\Components\TextEntry::make('name')
                    ->weight('bold'),
                Infolists\Components\TextEntry::make('slug')
                    ->copyable()
                    ->color('gray'),
                Infolists\Components\TextEntry::make('email')
                    ->icon('heroicon-m-envelope')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('phone')
                    ->icon('heroicon-m-phone')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('address')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('timezone')
                    ->badge()
                    ->color('gray'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('Registered')
                    ->dateTime(),
            ])->columns(3),

            SchemaComponents\Section::make('Users')->schema([
                Infolists\Components\RepeatableEntry::make('users')
                    ->schema([
                        Infolists\Components\ImageEntry::make('avatar')
                            ->circular()
                            ->size(36)
                            ->defaultImageUrl(fn($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record?->name ?? 'User') . '&color=6366f1&background=e0e7ff'),
                        Infolists\Components\TextEntry::make('name')
                            ->weight('semibold'),
                        Infolists\Components\TextEntry::make('email')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match($state) {
                                'active' => 'success',
                                'inactive' => 'gray',
                                'suspended' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(4),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'view' => Pages\ViewTenant::route('/{record}'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
