<?php

namespace App\Filament\Owner\Resources\FeedbackResource\Pages;

use App\Filament\Owner\Resources\FeedbackResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFeedback extends ViewRecord
{
    protected static string $resource = FeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Review'),
        ];
    }
}
