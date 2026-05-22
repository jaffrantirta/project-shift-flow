<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Pages\Reports\Concerns\ExportsReport;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LeaveReport extends Page implements HasTable
{
    use InteractsWithTable, ExportsReport;

    protected static string|\UnitEnum|null $navigationGroup = 'Reports';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Leave';
    protected static ?string $title = 'Leave Report';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.reports.report-table';

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        $companyId = auth()->user()?->company_id;

        return $table
            ->query(LeaveRequest::query()
                ->whereHas('user', fn($q) => $q->where('company_id', $companyId))
                ->with(['user.employeeProfile', 'leaveType', 'reviewedBy']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')->searchable()->sortable()->weight('semibold'),
                Tables\Columns\TextColumn::make('user.employeeProfile.job_title')
                    ->label('Job Title')->placeholder('—'),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('Leave Type')->badge()->color('indigo'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('From')->date('M j, Y')->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('To')->date('M j, Y')->sortable(),
                Tables\Columns\TextColumn::make('total_days')
                    ->label('Days')
                    ->formatStateUsing(fn($state) => number_format((float) $state, 1) . ' day(s)')
                    ->alignCenter()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state) => match($state) {
                        'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'gray',
                    })->sortable(),
                Tables\Columns\TextColumn::make('reason')
                    ->label('Reason')->limit(40)->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('reviewedBy.name')
                    ->label('Reviewed By')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Reviewed At')->dateTime('M j, Y')->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected']),
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label('Leave Type')->options(LeaveType::pluck('name', 'id')),
                Tables\Filters\Filter::make('date_range')->label('Date Range')
                    ->schema([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From')->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until')->native(false),
                    ])
                    ->query(fn(Builder $query, array $data) => $query
                        ->when($data['from'],  fn($q, $v) => $q->whereDate('start_date', '>=', $v))
                        ->when($data['until'], fn($q, $v) => $q->whereDate('end_date',   '<=', $v))),
                Tables\Filters\Filter::make('paid_only')->label('Paid Leave Only')
                    ->query(fn(Builder $query) => $query->whereHas('leaveType', fn($q) => $q->where('is_paid', true)))
                    ->toggle(),
            ])
            ->defaultSort('start_date', 'desc')->striped()->paginated([25, 50, 100]);
    }

    // ── ExportsReport contract ────────────────────────────────────────

    protected function getPdfView(): string { return 'reports.pdf.leave'; }
    protected function getCsvFilename(): string { return 'leave-report.csv'; }
    protected function getCsvHeaders(): array
    {
        return ['Employee', 'Job Title', 'Leave Type', 'Is Paid', 'Start Date', 'End Date', 'Total Days', 'Status', 'Reason', 'Reviewed By'];
    }

    protected function getCsvRecords(): Collection
    {
        $companyId = auth()->user()?->company_id;

        return LeaveRequest::whereHas('user', fn($q) => $q->where('company_id', $companyId))
            ->with(['user.employeeProfile', 'leaveType', 'reviewedBy'])
            ->get();
    }

    protected function getCsvRow(mixed $r): array
    {
        return [
            $r->user?->name,
            $r->user?->employeeProfile?->job_title,
            $r->leaveType?->name,
            $r->leaveType?->is_paid ? 'Yes' : 'No',
            $r->start_date->format('Y-m-d'),
            $r->end_date->format('Y-m-d'),
            number_format((float) $r->total_days, 1),
            $r->status,
            $r->reason,
            $r->reviewedBy?->name,
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
            ['label' => 'Total Requests', 'value' => $records->count()],
            ['label' => 'Approved',        'value' => $records->where('status', 'approved')->count()],
            ['label' => 'Pending',         'value' => $records->where('status', 'pending')->count()],
            ['label' => 'Rejected',        'value' => $records->where('status', 'rejected')->count()],
            ['label' => 'Total Days Off',  'value' => number_format($records->sum(fn($r) => (float)$r->total_days), 1)],
            ['label' => 'Paid Leave Days', 'value' => number_format($records->filter(fn($r) => $r->leaveType?->is_paid)->sum(fn($r) => (float)$r->total_days), 1)],
        ];
    }
}
