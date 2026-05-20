<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Schemas\Components as SchemaComponents;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Company';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('General')->schema([
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
                Tables\Columns\ImageColumn::make('logo')->square()->size(40),
                Tables\Columns\TextColumn::make('name')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('slug')->copyable()->color('gray'),
                Tables\Columns\TextColumn::make('email')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('timezone')->badge()->color('gray'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
