<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use Filament\Forms;

use Filament\Infolists;

use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components as SchemaComponents;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Leave Management';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-no-symbol';
    protected static ?string $navigationLabel = 'Leave Requests';
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
                Forms\Components\Select::make('leave_type_id')
                    ->label('Leave Type')
                    ->relationship('leaveType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->native(false),
                Forms\Components\DatePicker::make('end_date')
                    ->required()
                    ->native(false)
                    ->after('start_date'),
                Forms\Components\TextInput::make('total_days')
                    ->label('Total Days')
                    ->numeric()
                    ->required()
                    ->step(0.5),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\Textarea::make('reason')
                    ->rows(3)
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Type')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('From')
                    ->date('M j, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('To')
                    ->date('M j, Y'),
                Tables\Columns\TextColumn::make('total_days')
                    ->label('Days')
                    ->suffix(' day(s)')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Reviewed By')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending'),
                Tables\Filters\SelectFilter::make('leave_type')
                    ->relationship('leaveType', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                TableAction::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(LeaveRequest $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record): void {
                        $record->update(['status' => 'approved', 'reviewed_by' => Auth::id()]);
                        Notification::make()->title('Leave request approved')->success()->send();
                    }),
                TableAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(LeaveRequest $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function (LeaveRequest $record): void {
                        $record->update(['status' => 'rejected', 'reviewed_by' => Auth::id()]);
                        Notification::make()->title('Leave request rejected')->warning()->send();
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
                            $records->each(fn(LeaveRequest $r) => $r->update([
                                'status' => 'approved',
                                'reviewed_by' => Auth::id(),
                            ]));
                            Notification::make()->title('Leave requests approved')->success()->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaSection::make()->schema([
                Infolists\Components\TextEntry::make('user.name')->label('Employee'),
                Infolists\Components\TextEntry::make('leaveType.name')->label('Leave Type')->badge()->color('indigo'),
                Infolists\Components\TextEntry::make('start_date')->label('From')->date(),
                Infolists\Components\TextEntry::make('end_date')->label('To')->date(),
                Infolists\Components\TextEntry::make('total_days')->label('Total Days')->suffix(' day(s)'),
                Infolists\Components\TextEntry::make('status')->badge()
                    ->color(fn(string $state): string => match($state) {
                        'approved' => 'success', 'pending' => 'warning',
                        'rejected' => 'danger', default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('reason')->label('Reason')->placeholder('—')->columnSpanFull(),
                Infolists\Components\TextEntry::make('reviewedBy.name')->label('Reviewed By')->placeholder('—'),
                Infolists\Components\TextEntry::make('updated_at')->label('Reviewed At')->dateTime()->placeholder('—'),
            ])->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
        ];
    }
}
