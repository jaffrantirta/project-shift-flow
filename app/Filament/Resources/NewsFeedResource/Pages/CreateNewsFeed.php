<?php

namespace App\Filament\Resources\NewsFeedResource\Pages;

use App\Filament\Resources\NewsFeedResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewsFeed extends CreateRecord
{
    protected static string $resource = NewsFeedResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
