<?php

namespace App\Filament\Owner\Widgets;

use App\Filament\Owner\Resources\TenantResource;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTenantsWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Recently Registered Tenants';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Company::query()->latest()->limit(10)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->square()
                    ->size(36)
                    ->defaultImageUrl(fn(Company $r): string => 'https://ui-avatars.com/api/?name=' . urlencode($r->name) . '&color=6366f1&background=e0e7ff'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('email')
                    ->placeholder('—')
                    ->copyable(),
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
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Company $r): string => TenantResource::getUrl('view', ['record' => $r]))
                    ->color('indigo'),
            ])
            ->paginated(false);
    }
}
