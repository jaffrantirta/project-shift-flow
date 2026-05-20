<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Pages\Reports\Concerns\ExportsReport;
use App\Models\Location;
use App\Models\Timesheet;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StaffHoursReport extends Page implements HasTable
{
    use InteractsWithTable, ExportsReport;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Staff Hours';
    protected static ?string $title = 'Staff Hours Report';
    protected static ?int $navigationSort = 1;
    protected string $view = 'filament.pages.reports.report-table';

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Timesheet::query()
                    ->with(['user.employeeProfile', 'location', 'entries'])
                    ->withSum('entries', 'total_hours')
                    ->withSum('entries', 'overtime_hours')
                    ->withCount('entries')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('user.employeeProfile.job_title')
                    ->label('Job Title')->placeholder('—'),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')->badge()->color('indigo'),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn($record) => $record->period_start->format('M j') . ' – ' . $record->period_end->format('M j, Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('entries_count')
                    ->label('Days')->alignCenter()->sortable(),
                Tables\Columns\TextColumn::make('entries_sum_total_hours')
                    ->label('Total Hours')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 1) . ' hrs')
                    ->alignEnd()->sortable()->color('success')->weight('semibold'),
                Tables\Columns\TextColumn::make('entries_sum_overtime_hours')
                    ->label('Overtime')
                    ->formatStateUsing(fn($state) => $state > 0 ? number_format((float) $state, 1) . ' hrs' : '—')
                    ->alignEnd()->color('warning'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => match($state) {
                        'approved' => 'success', 'submitted' => 'warning', 'rejected' => 'danger', default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('submitted_at')
                    ->label('Submitted')->dateTime('M j, Y H:i')->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved')->dateTime('M j, Y H:i')->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['draft' => 'Draft', 'submitted' => 'Submitted', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Location')->options(Location::pluck('name', 'id')),
                Tables\Filters\Filter::make('period')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('period_from')->label('From')->native(false),
                        \Filament\Forms\Components\DatePicker::make('period_until')->label('Until')->native(false),
                    ])
                    ->query(fn(Builder $query, array $data) => $query
                        ->when($data['period_from'],  fn($q, $v) => $q->whereDate('period_start', '>=', $v))
                        ->when($data['period_until'], fn($q, $v) => $q->whereDate('period_end',   '<=', $v))),
            ])
            ->defaultSort('period_start', 'desc')
            ->striped()->paginated([25, 50, 100]);
    }

    // ── ExportsReport contract ────────────────────────────────────────

    protected function getPdfView(): string { return 'reports.pdf.staff-hours'; }
    protected function getCsvFilename(): string { return 'staff-hours-report.csv'; }
    protected function getCsvHeaders(): array
    {
        return ['Employee', 'Job Title', 'Location', 'Period Start', 'Period End', 'Days', 'Total Hours', 'Overtime Hours', 'Status'];
    }

    protected function getCsvRecords(): Collection
    {
        return Timesheet::with(['user.employeeProfile', 'location'])
            ->withSum('entries', 'total_hours')
            ->withSum('entries', 'overtime_hours')
            ->withCount('entries')
            ->get();
    }

    protected function getCsvRow(mixed $r): array
    {
        return [
            $r->user?->name,
            $r->user?->employeeProfile?->job_title,
            $r->location?->name,
            $r->period_start->format('Y-m-d'),
            $r->period_end->format('Y-m-d'),
            $r->entries_count,
            number_format((float) $r->entries_sum_total_hours, 1),
            number_format((float) $r->entries_sum_overtime_hours, 1),
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
        return [
            ['label' => 'Total Employees',  'value' => $records->pluck('user_id')->unique()->count()],
            ['label' => 'Total Timesheets', 'value' => $records->count()],
            ['label' => 'Total Hours',      'value' => number_format($records->sum(fn($r) => (float)($r->entries_sum_total_hours ?? 0)), 1)],
            ['label' => 'Overtime Hours',   'value' => number_format($records->sum(fn($r) => (float)($r->entries_sum_overtime_hours ?? 0)), 1)],
            ['label' => 'Approved',         'value' => $records->where('status', 'approved')->count()],
            ['label' => 'Pending',          'value' => $records->where('status', 'submitted')->count()],
        ];
    }
}
