<?php

namespace App\Livewire;

use App\Models\Location;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class ScheduleCalendar extends Component
{
    #[Url(as: 'week')]
    public string $weekStart = '';

    #[Url(as: 'loc')]
    public ?int $locationId = null;

    public bool $showModal = false;
    public ?int $selectedUserId = null;
    public ?string $selectedDate = null;
    public ?int $editingShiftId = null;

    public function mount(): void
    {
        if (empty($this->weekStart)) {
            $this->weekStart = now()->startOfWeek()->format('Y-m-d');
        }
    }

    public function previousWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->subWeek()->format('Y-m-d');
        unset($this->shifts);
    }

    public function nextWeek(): void
    {
        $this->weekStart = Carbon::parse($this->weekStart)->addWeek()->format('Y-m-d');
        unset($this->shifts);
    }

    public function goToToday(): void
    {
        $this->weekStart = now()->startOfWeek()->format('Y-m-d');
        unset($this->shifts);
    }

    public function updatedLocationId(): void
    {
        unset($this->shifts, $this->employees);
    }

    public function moveShift(int $shiftId, int $userId, string $date): void
    {
        $companyId = auth()->user()?->company_id;
        $shift = Shift::whereHas('location', fn($q) => $q->where('company_id', $companyId))->find($shiftId);
        if (! $shift) {
            return;
        }

        $tz = $shift->location?->timezone ?? 'UTC';
        $duration = $shift->start_datetime->diffInMinutes($shift->end_datetime);
        $localStart = $shift->start_datetime->setTimezone($tz);
        $newStart = Carbon::parse($date, $tz)
            ->setHour($localStart->hour)
            ->setMinute($localStart->minute)
            ->setTimezone('UTC');

        $shift->update([
            'user_id' => $userId,
            'start_datetime' => $newStart,
            'end_datetime' => $newStart->copy()->addMinutes($duration),
        ]);

        unset($this->shifts);
    }

    public function openAddModal(int $userId, string $date): void
    {
        $this->selectedUserId = $userId;
        $this->selectedDate = $date;
        $this->editingShiftId = null;
        $this->showModal = true;
    }

    public function openEditModal(int $shiftId): void
    {
        $this->editingShiftId = $shiftId;
        $this->selectedUserId = null;
        $this->selectedDate = null;
        $this->showModal = true;
    }

    #[Computed]
    public function weekStartCarbon(): Carbon
    {
        return Carbon::parse($this->weekStart)->startOfWeek();
    }

    #[Computed]
    public function weekDays(): array
    {
        return collect(range(0, 6))
            ->map(fn(int $i) => $this->weekStartCarbon->copy()->addDays($i))
            ->all();
    }

    #[Computed]
    public function employees(): Collection
    {
        $companyId = auth()->user()?->company_id;

        return User::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->whereIn('role', ['admin', 'employee'])
            ->when($this->locationId, fn($q) => $q->whereHas('locations', fn($q) => $q->where('location_id', $this->locationId)))
            ->with('employeeProfile')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function shifts(): Collection
    {
        $companyId = auth()->user()?->company_id;
        $start     = $this->weekStartCarbon;
        $end       = $start->copy()->endOfWeek();

        return Shift::query()
            ->whereBetween('start_datetime', [$start, $end])
            ->whereHas('location', fn($q) => $q->where('company_id', $companyId))
            ->when($this->locationId, fn($q) => $q->where('location_id', $this->locationId))
            ->with(['user', 'department', 'location'])
            ->get()
            ->groupBy(fn(Shift $s) => $s->user_id . '_' . $s->start_datetime->format('Y-m-d'));
    }

    #[Computed]
    public function locations(): Collection
    {
        $companyId = auth()->user()?->company_id;

        return Location::where('company_id', $companyId)->orderBy('name')->get();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.schedule-calendar');
    }
}
