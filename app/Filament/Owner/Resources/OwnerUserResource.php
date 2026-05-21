<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\OwnerUserResource\Pages;
use App\Models\User;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;

class OwnerUserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'All Users';
    protected static ?string $modelLabel = 'User';
    protected static ?string $pluralModelLabel = 'All Users';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->query(User::query()->with(['company', 'employeeProfile']))
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->size(36)
                    ->defaultImageUrl(fn(User $r): string => 'https://ui-avatars.com/api/?name=' . urlencode($r->name) . '&color=6366f1&background=e0e7ff'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Tenant')
                    ->badge()
                    ->color('indigo')
                    ->searchable()
                    ->placeholder('No tenant'),
                Tables\Columns\TextColumn::make('employeeProfile.job_title')
                    ->label('Job Title')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ]),
                Tables\Filters\SelectFilter::make('company')
                    ->label('Tenant')
                    ->relationship('company', 'name'),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make()->schema([
                Infolists\Components\ImageEntry::make('avatar')
                    ->circular()
                    ->size(80)
                    ->hiddenLabel()
                    ->defaultImageUrl(fn(User $r): string => 'https://ui-avatars.com/api/?name=' . urlencode($r->name) . '&size=80&color=6366f1&background=e0e7ff'),
                Infolists\Components\TextEntry::make('name')
                    ->hiddenLabel()
                    ->size(TextSize::Large)
                    ->weight('bold'),
                Infolists\Components\TextEntry::make('email')
                    ->icon('heroicon-m-envelope')
                    ->hiddenLabel()
                    ->copyable(),
                Infolists\Components\TextEntry::make('company.name')
                    ->label('Tenant')
                    ->badge()
                    ->color('indigo')
                    ->placeholder('No tenant'),
                Infolists\Components\TextEntry::make('status')
                    ->hiddenLabel()
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
            ])->columns(2),

            SchemaComponents\Section::make('Employee Profile')->schema([
                Infolists\Components\TextEntry::make('employeeProfile.employee_code')
                    ->label('Employee Code')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('employeeProfile.job_title')
                    ->label('Job Title')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('employeeProfile.employment_type')
                    ->label('Employment Type')
                    ->badge()
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('employeeProfile.hire_date')
                    ->label('Hire Date')
                    ->date()
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('Account Created')
                    ->dateTime(),
            ])->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOwnerUsers::route('/'),
            'view' => Pages\ViewOwnerUser::route('/{record}'),
        ];
    }
}
