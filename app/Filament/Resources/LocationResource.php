<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToAuthCompany;
use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components as SchemaComponents;

class LocationResource extends Resource
{
    use ScopedToAuthCompany;

    protected static ?string $model = Location::class;
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Locations';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Location Details')->schema([
                Forms\Components\Select::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->rows(2)
                    ->maxLength(500),
                Forms\Components\Select::make('timezone')
                    ->options(collect(timezone_identifiers_list())->mapWithKeys(fn($tz) => [$tz => $tz]))
                    ->searchable()
                    ->default('UTC')
                    ->required(),
            ])->columns(2),

            SchemaComponents\Section::make('Geofencing (optional)')->schema([
                Forms\Components\TextInput::make('lat')
                    ->label('Latitude')
                    ->numeric()
                    ->step(0.0000001),
                Forms\Components\TextInput::make('lng')
                    ->label('Longitude')
                    ->numeric()
                    ->step(0.0000001),
                Forms\Components\TextInput::make('radius_meters')
                    ->label('Radius (meters)')
                    ->numeric()
                    ->suffix('m'),
            ])->columns(3)->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('address')
                    ->limit(40)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('timezone')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('departments_count')
                    ->counts('departments')
                    ->label('Departments')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'name'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
