<div
    x-data="{
        dragging: null,
        dragStart(id) { this.dragging = id; },
        dragEnd()     { this.dragging = null; },
        drop(userId, date) {
            if (this.dragging !== null) {
                $wire.moveShift(this.dragging, userId, date);
                this.dragging = null;
            }
        },
    }"
    wire:key="schedule-calendar"
    class="space-y-4"
>
    {{-- ── Controls ────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        {{-- Week navigation --}}
        <div class="flex items-center gap-1.5">
            <button
                wire:click="previousWeek"
                class="rounded-lg border border-gray-200 p-1.5 text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
            >
                @svg('heroicon-o-chevron-left', 'h-4 w-4')
            </button>

            <span class="min-w-[210px] text-center text-sm font-semibold text-gray-800 dark:text-gray-100">
                {{ \Carbon\Carbon::parse($weekStart)->format('M j') }}
                –
                {{ \Carbon\Carbon::parse($weekStart)->addDays(6)->format('M j, Y') }}
            </span>

            <button
                wire:click="nextWeek"
                class="rounded-lg border border-gray-200 p-1.5 text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
            >
                @svg('heroicon-o-chevron-right', 'h-4 w-4')
            </button>

            <button
                wire:click="goToToday"
                class="ml-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                Today
            </button>
        </div>

        {{-- Filters + Add --}}
        <div class="flex items-center gap-2">
            <select
                wire:model.live="locationId"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm text-gray-700 shadow-sm focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            >
                <option value="">All Locations</option>
                @foreach($this->locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>

            <a
                href="{{ \App\Filament\Resources\ShiftResource::getUrl('create') }}"
                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                @svg('heroicon-o-plus', 'h-3.5 w-3.5')
                Add Shift
            </a>
        </div>
    </div>

    {{-- ── Calendar Grid ───────────────────────────────── --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <table class="min-w-[900px] w-full border-collapse">

            {{-- Day headers --}}
            <thead>
                <tr class="border-b border-gray-100 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-800">
                    <th class="w-48 border-r border-gray-100 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        Employee
                    </th>
                    @foreach($this->weekDays as $day)
                        <th class="px-2 py-3 text-center text-xs font-semibold uppercase tracking-wide
                            {{ $day->isToday() ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400' }}">
                            <div>{{ $day->format('D') }}</div>
                            <div class="mt-0.5 flex items-center justify-center">
                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-base font-bold
                                    {{ $day->isToday() ? 'bg-indigo-600 text-white' : 'text-gray-700 dark:text-gray-200' }}">
                                    {{ $day->format('j') }}
                                </span>
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            {{-- Employee rows --}}
            <tbody>
                @forelse($this->employees as $employee)
                    <tr class="group border-b border-gray-100 last:border-b-0 hover:bg-gray-50/40 dark:border-gray-700 dark:hover:bg-gray-700/20">

                        {{-- Employee name --}}
                        <td class="border-r border-gray-100 px-4 py-2 dark:border-gray-700">
                            <a
                                href="{{ \App\Filament\Resources\EmployeeResource::getUrl('view', ['record' => $employee]) }}"
                                class="flex items-center gap-2.5 hover:opacity-80"
                            >
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-900/60 dark:text-indigo-300">
                                    {{ strtoupper(substr($employee->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-medium text-gray-800 dark:text-gray-100">
                                        {{ $employee->name }}
                                    </div>
                                    <div class="truncate text-xs text-gray-400">
                                        {{ $employee->employeeProfile?->job_title ?? '—' }}
                                    </div>
                                </div>
                            </a>
                        </td>

                        {{-- Day cells --}}
                        @foreach($this->weekDays as $day)
                            @php $key = $employee->id . '_' . $day->format('Y-m-d') @endphp
                            <td
                                class="relative min-h-[64px] p-1 align-top border-r border-gray-100 last:border-r-0 dark:border-gray-700 transition-colors
                                    {{ $day->isToday() ? 'bg-indigo-50/20 dark:bg-indigo-900/5' : '' }}"
                                @dragover.prevent
                                @dragenter.prevent="$el.classList.add('bg-indigo-50', 'dark:bg-indigo-900/30', 'ring-2', 'ring-inset', 'ring-indigo-400')"
                                @dragleave="$el.classList.remove('bg-indigo-50', 'dark:bg-indigo-900/30', 'ring-2', 'ring-inset', 'ring-indigo-400')"
                                @drop.prevent="
                                    $el.classList.remove('bg-indigo-50', 'dark:bg-indigo-900/30', 'ring-2', 'ring-inset', 'ring-indigo-400');
                                    drop({{ $employee->id }}, '{{ $day->format('Y-m-d') }}')
                                "
                            >
                                {{-- Shift cards --}}
                                @if(isset($this->shifts[$key]))
                                    @foreach($this->shifts[$key] as $shift)
                                        @php
                                            $palette = ['#6366f1','#10b981','#f59e0b','#ec4899','#14b8a6','#f97316','#a855f7','#3b82f6'];
                                            $color = $palette[($shift->department_id ?? 0) % 8];
                                        @endphp
                                        <div
                                            draggable="true"
                                            @dragstart="dragStart({{ $shift->id }})"
                                            @dragend="dragEnd()"
                                            wire:click.stop="openEditModal({{ $shift->id }})"
                                            class="mb-1 cursor-grab select-none rounded-md px-2 py-1 text-xs text-white shadow-sm hover:brightness-110 active:cursor-grabbing active:opacity-70 transition-all"
                                            style="background-color: {{ $color }}"
                                            title="{{ $shift->user?->name }}: {{ $shift->start_datetime->format('H:i') }}–{{ $shift->end_datetime->format('H:i') }}"
                                        >
                                            <div class="font-semibold leading-tight">
                                                {{ $shift->start_datetime->format('H:i') }}–{{ $shift->end_datetime->format('H:i') }}
                                            </div>
                                            @if($shift->department)
                                                <div class="truncate opacity-90">{{ $shift->department->name }}</div>
                                            @endif
                                            @if($shift->status === 'cancelled')
                                                <div class="mt-0.5 text-[10px] font-bold uppercase opacity-90">Cancelled</div>
                                            @endif
                                        </div>
                                    @endforeach
                                @endif

                                {{-- Add shift (+) on hover --}}
                                <button
                                    wire:click.stop="openAddModal({{ $employee->id }}, '{{ $day->format('Y-m-d') }}')"
                                    class="absolute inset-0 flex h-full w-full items-center justify-center opacity-0 transition-opacity group-hover:opacity-100 hover:bg-indigo-50/60 dark:hover:bg-indigo-900/20"
                                    @click.stop
                                >
                                    @svg('heroicon-o-plus', 'h-4 w-4 text-indigo-400')
                                </button>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-20 text-center text-sm text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                @svg('heroicon-o-users', 'h-10 w-10 text-gray-300')
                                <p>No employees found.</p>
                                <a href="{{ \App\Filament\Resources\EmployeeResource::getUrl('create') }}" class="text-indigo-600 hover:underline">
                                    Add your first employee →
                                </a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Shift Modal ─────────────────────────────────── --}}
    @if($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
            wire:click="$set('showModal', false)"
        >
            <div
                class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900"
                @click.stop
            >
                <div class="mb-1 flex items-center gap-2">
                    @svg('heroicon-o-clock', 'h-5 w-5 text-indigo-500')
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $editingShiftId ? 'Edit Shift' : 'New Shift' }}
                    </h3>
                </div>

                @if(!$editingShiftId && $selectedDate)
                    <p class="mb-4 text-sm text-gray-500">
                        Creating shift for
                        <span class="font-medium text-gray-700 dark:text-gray-200">
                            {{ \Carbon\Carbon::parse($selectedDate)->format('D, M j') }}
                        </span>
                    </p>
                @endif

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        wire:click="$set('showModal', false)"
                        class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>

                    @if($editingShiftId)
                        <a
                            href="{{ \App\Filament\Resources\ShiftResource::getUrl('edit', ['record' => $editingShiftId]) }}"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Open Editor
                        </a>
                    @else
                        <a
                            href="{{ \App\Filament\Resources\ShiftResource::getUrl('create') }}?user_id={{ $selectedUserId }}&date={{ $selectedDate }}"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            Create Shift
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Wire loading overlay --}}
    <div wire:loading.flex class="fixed inset-0 z-40 items-center justify-center bg-white/30 backdrop-blur-[1px]">
        <div class="rounded-xl bg-white p-4 shadow-lg dark:bg-gray-800">
            <x-filament::loading-indicator class="h-6 w-6 text-indigo-600" />
        </div>
    </div>
</div>
