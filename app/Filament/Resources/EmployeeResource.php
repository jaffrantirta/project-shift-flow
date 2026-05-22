<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToAuthCompany;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\User;
use Filament\Forms;

use Filament\Infolists;
use Filament\Schemas\Components\Grid as SchemaGrid;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Support\Enums\TextSize;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components as SchemaComponents;

class EmployeeResource extends Resource
{
    use ScopedToAuthCompany;

    protected static ?string $model = User::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Organization';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Employees';
    protected static ?string $modelLabel = 'Employee';
    protected static ?string $pluralModelLabel = 'Employees';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return static::applyCompanyScope(parent::getEloquentQuery())
            ->whereIn('role', ['admin', 'employee']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaGrid::make(['default' => 1, 'lg' => 3])->schema([
                SchemaComponents\Section::make('Personal Information')->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->tel()
                        ->maxLength(30),
                    Forms\Components\Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'suspended' => 'Suspended',
                        ])
                        ->default('active')
                        ->required(),
                    Forms\Components\FileUpload::make('avatar')
                        ->image()
                        ->directory('avatars')
                        ->imageEditor()
                        ->columnSpanFull(),
                ])->columns(2)->columnSpan(2),

                SchemaComponents\Section::make('Access')->schema([
                    Forms\Components\Select::make('role')
                        ->options([
                            'admin'    => 'Admin',
                            'employee' => 'Employee',
                        ])
                        ->default('employee')
                        ->required(),
                    Forms\Components\TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->required(fn(string $operation): bool => $operation === 'create')
                        ->dehydrated(fn(?string $state): bool => filled($state))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('pin')
                        ->label('PIN')
                        ->password()
                        ->maxLength(10),
                ])->columnSpan(1),
            ])->columnSpanFull(),

            SchemaComponents\Section::make('Employee Profile')
                ->relationship('employeeProfile')
                ->schema([
                    Forms\Components\TextInput::make('employee_code')
                        ->maxLength(60),
                    Forms\Components\TextInput::make('job_title')
                        ->maxLength(255),
                    Forms\Components\Select::make('employment_type')
                        ->options([
                            'full_time' => 'Full Time',
                            'part_time' => 'Part Time',
                            'casual' => 'Casual',
                            'contractor' => 'Contractor',
                        ]),
                    Forms\Components\Select::make('pay_type')
                        ->options([
                            'hourly' => 'Hourly',
                            'salary' => 'Salary',
                        ]),
                    Forms\Components\TextInput::make('pay_rate')
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01),
                    Forms\Components\DatePicker::make('hire_date')
                        ->native(false),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn(User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=6366f1&background=e0e7ff'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('employeeProfile.job_title')
                    ->label('Job Title')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('employeeProfile.employment_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(?string $state): string => match($state) {
                        'full_time' => 'success',
                        'part_time' => 'indigo',
                        'casual' => 'warning',
                        'contractor' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state): string => match($state) {
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'casual' => 'Casual',
                        'contractor' => 'Contractor',
                        default => '—',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
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
                Tables\Filters\SelectFilter::make('employment_type')
                    ->label('Employment Type')
                    ->relationship('employeeProfile', 'employment_type')
                    ->options([
                        'full_time' => 'Full Time',
                        'part_time' => 'Part Time',
                        'casual' => 'Casual',
                        'contractor' => 'Contractor',
                    ]),
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
            ->defaultSort('name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaGrid::make(['default' => 1, 'xl' => 4])->schema([
                SchemaSection::make()->schema([
                    Infolists\Components\ImageEntry::make('avatar')
                        ->circular()
                        ->size(80)
                        ->hiddenLabel()
                        ->defaultImageUrl(fn(User $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&size=80&color=6366f1&background=e0e7ff'),
                    Infolists\Components\TextEntry::make('name')
                        ->hiddenLabel()
                        ->size(TextSize::Large)
                        ->weight('bold'),
                    Infolists\Components\TextEntry::make('email')
                        ->icon('heroicon-m-envelope')
                        ->hiddenLabel()
                        ->copyable(),
                    Infolists\Components\TextEntry::make('phone')
                        ->icon('heroicon-m-phone')
                        ->hiddenLabel()
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('status')
                        ->hiddenLabel()
                        ->badge()
                        ->color(fn(string $state): string => match($state) {
                            'active' => 'success',
                            'inactive' => 'gray',
                            'suspended' => 'danger',
                            default => 'gray',
                        }),
                ])->columnSpan(1),

                SchemaGroup::make([
                    SchemaSection::make('Employee Profile')->schema([
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
                        Infolists\Components\TextEntry::make('employeeProfile.pay_type')
                            ->label('Pay Type')
                            ->badge()
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('employeeProfile.pay_rate')
                            ->label('Pay Rate')
                            ->money('USD')
                            ->placeholder('—'),
                        Infolists\Components\TextEntry::make('employeeProfile.hire_date')
                            ->label('Hire Date')
                            ->date()
                            ->placeholder('—'),
                    ])->columns(3),

                    SchemaSection::make('Account')->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Member Since')
                            ->date(),
                        Infolists\Components\TextEntry::make('email_verified_at')
                            ->label('Email Verified')
                            ->dateTime()
                            ->placeholder('Not verified'),
                    ])->columns(2),
                ])->columnSpan(3),
            ])->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
