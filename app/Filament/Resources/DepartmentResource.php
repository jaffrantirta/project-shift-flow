<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToAuthCompany;
use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
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

class DepartmentResource extends Resource
{
    use ScopedToAuthCompany;

    const COMPANY_SCOPE = 'via_location';
    protected static ?string $model = Department::class;
    protected static string|\UnitEnum|null $navigationGroup = 'System';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationLabel = 'Departments';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make()->schema([
                Forms\Components\Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('location.company.name')
                    ->label('Company')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->relationship('location', 'name'),
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
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
