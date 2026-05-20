<?php

namespace App\Filament\Resources\NewsFeedResource\Pages;

use App\Filament\Resources\NewsFeedResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNewsFeed extends EditRecord
{
    protected static string $resource = NewsFeedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
