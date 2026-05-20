<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ScheduleBuilder extends Page
{
    protected static string|\UnitEnum|null $navigationGroup = 'Scheduling';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Schedule Builder';
    protected static ?string $title = 'Schedule Builder';
    protected static ?int $navigationSort = 1;

    // Non-static in Filament v5
    protected string $view = 'filament.pages.schedule-builder';
}
