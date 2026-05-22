<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Pages\Reports\Concerns\ExportsReport;
use App\Models\Department;
use App\Models\Location;
use App\Models\Shift;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ShiftSummaryReport extends Page implements HasTable
{
    use InteractsWithTable, ExportsReport;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Shift Summary';
    protected static ?string $title = 'Shift Summary Report';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.reports.report-table';

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        $companyId = auth()->user()?->company_id;

        return $table
            ->query(
                Shift::query()
                    ->whereHas('location', fn($q) => $q->where('company_id', $companyId))
                    ->with(['user.employeeProfile', 'location', 'department', 'schedule'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Date')
                    ->formatStateUsing(fn($record) => $record->start_datetime->setTimezone($record->location?->timezone ?? 'UTC')->format('D, M j, Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('user.employeeProfile.job_title')
                    ->label('Job Title')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Shift')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_datetime')
                    ->label('Start')
                    ->formatStateUsing(fn($record) => $record->start_datetime->setTimezone($record->location?->timezone ?? 'UTC')->format('H:i'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_datetime')
                    ->label('End')
                    ->formatStateUsing(fn($record) => $record->end_datetime->setTimezone($record->location?->timezone ?? 'UTC')->format('H:i')),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->state(function ($record): string {
                        $minutes = $record->start_datetime->diffInMinutes($record->end_datetime) - ($record->break_duration_minutes ?? 0);
                        return sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
                    })
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('break_duration_minutes')
                    ->label('Break')
                    ->formatStateUsing(fn($state) => ($state ?? 0) . ' min')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => match($state) {
                        'confirmed'  => 'success',
                        'draft'      => 'gray',
                        'published'  => 'indigo',
                        'cancelled'  => 'danger',
                        default      => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'     => 'Draft',
                        'published' => 'Published',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(Location::where('company_id', auth()->user()?->company_id)->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->options(Department::whereHas('location', fn($q) => $q->where('company_id', auth()->user()?->company_id))->pluck('name', 'id')),
                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From')->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until')->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['from'],  fn($q, $v) => $q->whereDate('start_datetime', '>=', $v))
                            ->when($data['until'], fn($q, $v) => $q->whereDate('start_datetime', '<=', $v));
                    }),
            ])
            ->defaultSort('start_datetime', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    // ── ExportsReport contract ────────────────────────────────────────

    protected function getPdfView(): string { return 'reports.pdf.shift-summary'; }
    protected function getCsvFilename(): string { return 'shift-summary-report.csv'; }
    protected function getCsvHeaders(): array
    {
        return ['Date', 'Employee', 'Job Title', 'Location', 'Department', 'Shift', 'Start', 'End', 'Break (min)', 'Net Duration', 'Status'];
    }

    protected function getCsvRecords(): Collection
    {
        $companyId = auth()->user()?->company_id;

        return Shift::whereHas('location', fn($q) => $q->where('company_id', $companyId))
            ->with(['user.employeeProfile', 'location', 'department'])
            ->get();
    }

    protected function getCsvRow(mixed $r): array
    {
        $tz      = $r->location?->timezone ?? 'UTC';
        $minutes = $r->start_datetime->diffInMinutes($r->end_datetime) - ($r->break_duration_minutes ?? 0);

        return [
            $r->start_datetime->setTimezone($tz)->format('Y-m-d'),
            $r->user?->name,
            $r->user?->employeeProfile?->job_title,
            $r->location?->name,
            $r->department?->name,
            $r->title,
            $r->start_datetime->setTimezone($tz)->format('H:i'),
            $r->end_datetime->setTimezone($tz)->format('H:i'),
            $r->break_duration_minutes ?? 0,
            sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60),
            $r->status,
        ];
    }

    protected function getPdfData(): array
    {
        return ['records' => $this->getCsvRecords()];
    }

    protected function getPdfStats(): array
    {
        $records = $this->getCsvRecords();

        $totalMinutes = $records->sum(function ($r) {
            return $r->start_datetime->diffInMinutes($r->end_datetime) - ($r->break_duration_minutes ?? 0);
        });

        return [
            ['label' => 'Total Shifts',   'value' => $records->count()],
            ['label' => 'Confirmed',       'value' => $records->where('status', 'confirmed')->count()],
            ['label' => 'Published',       'value' => $records->where('status', 'published')->count()],
            ['label' => 'Cancelled',       'value' => $records->where('status', 'cancelled')->count()],
            ['label' => 'Total Hours',     'value' => sprintf('%dh %02dm', intdiv($totalMinutes, 60), $totalMinutes % 60)],
        ];
    }
}
