<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\ScopedToAuthCompany;
use App\Filament\Resources\TaskResource\Pages;
use App\Models\Task;
use Filament\Forms;

use Filament\Infolists;

use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Actions\Action as TableAction;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Section as SchemaSection;
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components as SchemaComponents;

class TaskResource extends Resource
{
    use ScopedToAuthCompany;

    protected static ?string $model = Task::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Task Management';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Tasks';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Task Details')->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('description')
                    ->toolbarButtons(['bold', 'italic', 'bulletList', 'orderedList', 'link'])
                    ->columnSpanFull(),
                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                        'urgent' => 'Urgent',
                    ])
                    ->default('medium')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->native(false),
                Forms\Components\Select::make('created_by')
                    ->label('Created By')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ])->columns(2),

            SchemaComponents\Section::make('Assign To')->schema([
                Forms\Components\Select::make('assignees')
                    ->label('Assignees')
                    ->relationship('assignees', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('semibold')
                    ->limit(50),
                Tables\Columns\TextColumn::make('priority')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'medium' => 'indigo',
                        'low' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'completed' => 'success',
                        'in_progress' => 'indigo',
                        'pending' => 'warning',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        'in_progress' => 'In Progress',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due')
                    ->date('M j, Y')
                    ->color(fn(Task $record): string => $record->due_date?->isPast() && $record->status !== 'completed' ? 'danger' : 'gray')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Created By'),
                Tables\Columns\TextColumn::make('assignees_count')
                    ->label('Assignees')
                    ->counts('assignees')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'urgent' => 'Urgent']),
            ])
            ->actions([
                EditAction::make(),
                TableAction::make('complete')
                    ->label('Mark Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Task $record): bool => !in_array($record->status, ['completed', 'cancelled']))
                    ->requiresConfirmation()
                    ->action(function (Task $record): void {
                        $record->update(['status' => 'completed']);
                        Notification::make()->title('Task marked as complete')->success()->send();
                    }),
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
            SchemaSection::make()->schema([
                Infolists\Components\TextEntry::make('title')->hiddenLabel()->size('lg')->weight('bold')->columnSpanFull(),
                Infolists\Components\TextEntry::make('priority')->badge()
                    ->color(fn($state) => match($state) {
                        'urgent' => 'danger', 'high' => 'warning', 'medium' => 'indigo', default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('status')->badge()
                    ->color(fn($state) => match($state) {
                        'completed' => 'success', 'in_progress' => 'indigo', 'pending' => 'warning', default => 'gray',
                    }),
                Infolists\Components\TextEntry::make('due_date')->label('Due Date')->date()->placeholder('No due date'),
                Infolists\Components\TextEntry::make('createdBy.name')->label('Created By'),
                Infolists\Components\TextEntry::make('description')->html()->columnSpanFull()->placeholder('No description'),
            ])->columns(4),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
