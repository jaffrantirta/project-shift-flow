<?php

namespace App\Filament\Owner\Resources;

use App\Filament\Owner\Resources\FeedbackResource\Pages;
use App\Models\Feedback;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components as SchemaComponents;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Feedback';
    protected static ?string $modelLabel = 'Feedback';
    protected static ?string $pluralModelLabel = 'Feedback';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Owner Review')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'in_review' => 'In Review',
                        'resolved'  => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('admin_notes')
                    ->label('Admin Notes')
                    ->rows(4)
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Feedback::query()->with(['company', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Tenant')
                    ->badge()
                    ->color('indigo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Submitted By')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'bug'            => 'danger',
                        'feature_request'=> 'indigo',
                        'complaint'      => 'warning',
                        'suggestion'     => 'info',
                        default          => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        'general'         => 'General',
                        'bug'             => 'Bug / Issue',
                        'feature_request' => 'Feature Request',
                        'complaint'       => 'Complaint',
                        'suggestion'      => 'Suggestion',
                        default           => $state,
                    }),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn(?int $state): string => $state ? str_repeat('★', $state) . str_repeat('☆', 5 - $state) : '—')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'admin' ? 'gray' : 'success'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'pending'   => 'warning',
                        'in_review' => 'indigo',
                        'resolved'  => 'success',
                        'dismissed' => 'gray',
                        default     => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'in_review' => 'In Review',
                        'resolved'  => 'Resolved',
                        'dismissed' => 'Dismissed',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'general'         => 'General',
                        'bug'             => 'Bug / Issue',
                        'feature_request' => 'Feature Request',
                        'complaint'       => 'Complaint',
                        'suggestion'      => 'Suggestion',
                    ]),
                Tables\Filters\SelectFilter::make('company')
                    ->label('Tenant')
                    ->relationship('company', 'name'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->label('Review'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            SchemaComponents\Section::make('Feedback Details')->schema([
                Infolists\Components\TextEntry::make('company.name')
                    ->label('Tenant')
                    ->badge()
                    ->color('indigo'),
                Infolists\Components\TextEntry::make('user.name')
                    ->label('Submitted By')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('source')
                    ->badge()
                    ->color(fn(string $state): string => $state === 'admin' ? 'gray' : 'success'),
                Infolists\Components\TextEntry::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'bug'             => 'danger',
                        'feature_request' => 'indigo',
                        'complaint'       => 'warning',
                        'suggestion'      => 'info',
                        default           => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match($state) {
                        'general'         => 'General',
                        'bug'             => 'Bug / Issue',
                        'feature_request' => 'Feature Request',
                        'complaint'       => 'Complaint',
                        'suggestion'      => 'Suggestion',
                        default           => $state,
                    }),
                Infolists\Components\TextEntry::make('rating')
                    ->formatStateUsing(fn(?int $state): string => $state ? str_repeat('★', $state) . str_repeat('☆', 5 - $state) : '—')
                    ->placeholder('—'),
                Infolists\Components\TextEntry::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'pending'   => 'warning',
                        'in_review' => 'indigo',
                        'resolved'  => 'success',
                        'dismissed' => 'gray',
                        default     => 'gray',
                    }),
                Infolists\Components\TextEntry::make('subject')
                    ->columnSpanFull()
                    ->weight('semibold'),
                Infolists\Components\TextEntry::make('message')
                    ->columnSpanFull(),
                Infolists\Components\TextEntry::make('admin_notes')
                    ->label('Admin Notes')
                    ->columnSpanFull()
                    ->placeholder('No notes yet'),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('Submitted')
                    ->dateTime(),
            ])->columns(3),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFeedbacks::route('/'),
            'view'   => Pages\ViewFeedback::route('/{record}'),
            'edit'   => Pages\EditFeedback::route('/{record}/edit'),
        ];
    }
}
