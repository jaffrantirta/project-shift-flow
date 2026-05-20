<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Task'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Active')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', ['pending', 'in_progress'])),
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', 'completed')),
            'all' => Tab::make('All'),
        ];
    }
}
