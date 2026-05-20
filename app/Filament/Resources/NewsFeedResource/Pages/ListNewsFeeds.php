<?php

namespace App\Filament\Resources\NewsFeedResource\Pages;

use App\Filament\Resources\NewsFeedResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNewsFeeds extends ListRecords
{
    protected static string $resource = NewsFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Announcement'),
        ];
    }
}
