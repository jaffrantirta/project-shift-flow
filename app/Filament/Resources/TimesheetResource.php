<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimesheetResource\Pages;
use App\Models\Timesheet;
use Filament\Forms;

use Filament\Infolists;

use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components as SchemaComponents;

class TimesheetResource extends Resource
{
    protected static ?string $model = Timesheet::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Time & Attendance';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationLabel = 'Timesheets';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make()->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Employee')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('period_start')
                    ->label('Period Start')
                    ->required()
                    ->native(false),
                Forms\Components\DatePicker::make('period_end')
                    ->label('Period End')
                    ->required()
                    ->native(false)
                    ->after('period_start'),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('draft')
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn(Timesheet $record): string =>
                        $record->period_start->format('M j') . ' – ' . $record->period_end->format('M j, Y')
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->since()
                    ->placeholder('Not submitted'),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Approved By')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'approved' => 'success',
                        'submitted' => 'warning',
                        'rejected' => 'danger',
                        'draft' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'submitted' => 'Submitted',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('submitted'),
                Tables\Filters\SelectFilter::make('location')
                    ->relationship('location', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                TableAction::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Timesheet $record): bool => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->action(function (Timesheet $record): void {
                        $record->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                            'approved_at' => now(),
                        ]);
                        Notification::make()->title('Timesheet approved')->success()->send();
                    }),
                TableAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Timesheet $record): bool => $record->status === 'submitted')
                    ->requiresConfirmation()
                    ->action(function (Timesheet $record): void {
                        $record->update(['status' => 'rejected']);
                        Notification::make()->title('Timesheet rejected')->warning()->send();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(fn(Timesheet $r) => $r->update([
                                'status' => 'approved',
                                'approved_by' => Auth::id(),
                                'approved_at' => now(),
                            ]));
                            Notification::make()->title('Timesheets approved')->success()->send();
                        }),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaSection::make('Timesheet Details')->schema([
                Infolists\Components\TextEntry::make('user.name')->label('Employee'),
                Infolists\Components\TextEntry::make('location.name')->label('Location')->badge()->color('indigo'),
                Infolists\Components\TextEntry::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn(Timesheet $record): string =>
                        $record->period_start->format('M j, Y') . ' – ' . $record->period_end->format('M j, Y')
                    ),
                Infolists\Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'approved' => 'success', 'submitted' => 'warning',
                        'rejected' => 'danger', default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('submitted_at')->label('Submitted')->dateTime()->placeholder('—'),
                Infolists\Components\TextEntry::make('approvedBy.name')->label('Approved By')->placeholder('—'),
                Infolists\Components\TextEntry::make('approved_at')->label('Approved At')->dateTime()->placeholder('—'),
            ])->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTimesheets::route('/'),
            'create' => Pages\CreateTimesheet::route('/create'),
            'view'   => Pages\ViewTimesheet::route('/{record}'),
            'edit'   => Pages\EditTimesheet::route('/{record}/edit'),
        ];
    }
}
