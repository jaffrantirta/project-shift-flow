<?php

namespace App\Filament\Owner\Resources\FeedbackResource\Pages;

use App\Filament\Owner\Resources\FeedbackResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFeedback extends EditRecord
{
    protected static string $resource = FeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
