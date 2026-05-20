@extends('reports.pdf.layout')

@section('content')
<table>
  <thead>
    <tr>
      <th>Employee</th>
      <th>Job Title</th>
      <th>Location</th>
      <th>Period</th>
      <th class="right">Days</th>
      <th class="right">Total Hrs</th>
      <th class="right">Overtime</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($records as $r)
    <tr>
      <td class="bold">{{ $r->user?->name ?? '—' }}</td>
      <td class="muted">{{ $r->user?->employeeProfile?->job_title ?? '—' }}</td>
      <td><span class="badge badge-indigo">{{ $r->location?->name ?? '—' }}</span></td>
      <td>{{ $r->period_start->format('M j') }} – {{ $r->period_end->format('M j, Y') }}</td>
      <td class="right center">{{ $r->entries_count }}</td>
      <td class="right bold">{{ number_format((float)$r->entries_sum_total_hours, 1) }} hrs</td>
      <td class="right">
        @if((float)$r->entries_sum_overtime_hours > 0)
          <span class="badge badge-warning">{{ number_format((float)$r->entries_sum_overtime_hours, 1) }} hrs</span>
        @else
          <span class="muted">—</span>
        @endif
      </td>
      <td>
        @php
          $badge = match($r->status) { 'approved'=>'success','submitted'=>'warning','rejected'=>'danger', default=>'gray' };
        @endphp
        <span class="badge badge-{{ $badge }}">{{ ucfirst($r->status) }}</span>
      </td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center;padding:20px;color:#9ca3af;">No records found.</td></tr>
    @endforelse
  </tbody>
</table>
@endsection
