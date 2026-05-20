<?php

namespace App\Filament\Resources\ShiftResource\Pages;

use App\Filament\Resources\ShiftResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShift extends EditRecord
{
    protected static string $resource = ShiftResource::class;

    protected static ?string $title = 'Shift Detail';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirm Shift')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn(): bool => $this->getRecord()->status === 'scheduled')
                ->action(fn() => $this->getRecord()->update(['status' => 'confirmed'])),
            Actions\DeleteAction::make(),
        ];
    }
}
