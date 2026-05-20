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

class LaborCostReport extends Page implements HasTable
{
    use InteractsWithTable, ExportsReport;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Labor Cost';
    protected static ?string $title = 'Labor Cost Report';
    protected static ?int $navigationSort = 3;
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
                    ->with(['user.employeeProfile', 'location'])
                    ->withSum('entries', 'total_hours')
                    ->withSum('entries', 'overtime_hours')
                    ->where('status', 'approved')
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('user.employeeProfile.job_title')
                    ->label('Job Title')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('user.employeeProfile.employment_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => match($state) {
                        'full_time'  => 'Full Time',
                        'part_time'  => 'Part Time',
                        'casual'     => 'Casual',
                        'contractor' => 'Contractor',
                        default      => '—',
                    })
                    ->color(fn(?string $state) => match($state) {
                        'full_time'  => 'success',
                        'part_time'  => 'indigo',
                        'casual'     => 'warning',
                        default      => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.employeeProfile.pay_type')
                    ->label('Pay Type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn(?string $state) => ucfirst($state ?? '—')),
                Tables\Columns\TextColumn::make('user.employeeProfile.pay_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn($state) => $state ? 'Rp ' . number_format((float) $state, 0, ',', '.') : '—')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('location.name')
                    ->label('Location')
                    ->badge()
                    ->color('indigo'),
                Tables\Columns\TextColumn::make('period_start')
                    ->label('Period')
                    ->formatStateUsing(fn($record) => $record->period_start->format('M j') . ' – ' . $record->period_end->format('M j, Y'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('entries_sum_total_hours')
                    ->label('Hours Worked')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 1) . ' hrs')
                    ->alignEnd()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estimated_cost')
                    ->label('Estimated Cost')
                    ->state(function ($record): string {
                        $hours    = (float) ($record->entries_sum_total_hours ?? 0);
                        $rate     = (float) ($record->user?->employeeProfile?->pay_rate ?? 0);
                        $payType  = $record->user?->employeeProfile?->pay_type;

                        if ($rate === 0.0) return '—';

                        $cost = $payType === 'hourly' ? $hours * $rate : $rate;
                        return 'Rp ' . number_format($cost, 0, ',', '.');
                    })
                    ->alignEnd()
                    ->weight('semibold')
                    ->color('success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Location')
                    ->options(Location::pluck('name', 'id')),
                Tables\Filters\Filter::make('period')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('period_from')->label('From')->native(false),
                        \Filament\Forms\Components\DatePicker::make('period_until')->label('Until')->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query
                            ->when($data['period_from'],  fn($q, $v) => $q->whereDate('period_start', '>=', $v))
                            ->when($data['period_until'], fn($q, $v) => $q->whereDate('period_end',   '<=', $v));
                    }),
                Tables\Filters\SelectFilter::make('employment_type')
                    ->label('Employment Type')
                    ->options([
                        'full_time'  => 'Full Time',
                        'part_time'  => 'Part Time',
                        'casual'     => 'Casual',
                        'contractor' => 'Contractor',
                    ])
                    ->query(fn(Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn($q, $v) => $q->whereHas('user.employeeProfile', fn($q) => $q->where('employment_type', $v))
                    )),
            ])
            ->defaultSort('period_start', 'desc')
            ->striped()
            ->paginated([25, 50, 100]);
    }

    // ── ExportsReport contract ────────────────────────────────────────

    protected function getPdfView(): string { return 'reports.pdf.labor-cost'; }
    protected function getCsvFilename(): string { return 'labor-cost-report.csv'; }
    protected function getCsvHeaders(): array
    {
        return ['Employee', 'Job Title', 'Employment Type', 'Pay Type', 'Pay Rate', 'Location', 'Period Start', 'Period End', 'Hours Worked', 'Estimated Cost'];
    }

    protected function getCsvRecords(): Collection
    {
        return Timesheet::with(['user.employeeProfile', 'location'])
            ->withSum('entries', 'total_hours')
            ->where('status', 'approved')
            ->get();
    }

    protected function getCsvRow(mixed $r): array
    {
        $hours   = (float) ($r->entries_sum_total_hours ?? 0);
        $rate    = (float) ($r->user?->employeeProfile?->pay_rate ?? 0);
        $payType = $r->user?->employeeProfile?->pay_type;
        $cost    = ($payType === 'hourly' && $rate > 0) ? $hours * $rate : ($rate > 0 ? $rate : 0);

        return [
            $r->user?->name,
            $r->user?->employeeProfile?->job_title,
            $r->user?->employeeProfile?->employment_type,
            $payType,
            'Rp ' . number_format($rate, 0, ',', '.'),
            $r->location?->name,
            $r->period_start->format('Y-m-d'),
            $r->period_end->format('Y-m-d'),
            number_format($hours, 1),
            'Rp ' . number_format($cost, 0, ',', '.'),
        ];
    }

    protected function getPdfData(): array
    {
        return ['records' => $this->getCsvRecords()];
    }

    protected function getPdfStats(): array
    {
        $records = $this->getCsvRecords();

        $totalCost = $records->sum(function ($r) {
            $hours   = (float) ($r->entries_sum_total_hours ?? 0);
            $rate    = (float) ($r->user?->employeeProfile?->pay_rate ?? 0);
            $payType = $r->user?->employeeProfile?->pay_type;
            return ($payType === 'hourly' && $rate > 0) ? $hours * $rate : ($rate > 0 ? $rate : 0);
        });

        return [
            ['label' => 'Total Employees',  'value' => $records->pluck('user_id')->unique()->count()],
            ['label' => 'Total Timesheets', 'value' => $records->count()],
            ['label' => 'Total Hours',      'value' => number_format($records->sum(fn($r) => (float)($r->entries_sum_total_hours ?? 0)), 1)],
            ['label' => 'Estimated Cost',   'value' => 'Rp ' . number_format($totalCost, 0, ',', '.')],
        ];
    }
}
