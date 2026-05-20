<?php

namespace App\Filament\Resources\NewsFeedResource\Pages;

use App\Filament\Resources\NewsFeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNewsFeed extends ViewRecord
{
    protected static string $resource = NewsFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
