@extends('reports.pdf.layout')

@section('content')
<table>
  <thead>
    <tr>
      <th>Date</th>
      <th>Employee</th>
      <th>Department</th>
      <th>Location</th>
      <th>Shift</th>
      <th>Start</th>
      <th>End</th>
      <th class="right">Break</th>
      <th class="right">Duration</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($records as $r)
    @php
      $minutes = $r->start_datetime->diffInMinutes($r->end_datetime) - ($r->break_duration_minutes ?? 0);
      $dur = sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60);
      $badge = match($r->status) { 'confirmed'=>'success','draft'=>'gray','published'=>'indigo','cancelled'=>'danger', default=>'gray' };
    @endphp
    <tr>
      <td>{{ $r->start_datetime->format('D, M j') }}</td>
      <td class="bold">{{ $r->user?->name ?? '—' }}</td>
      <td><span class="badge badge-gray">{{ $r->department?->name ?? '—' }}</span></td>
      <td><span class="badge badge-indigo">{{ $r->location?->name ?? '—' }}</span></td>
      <td class="muted">{{ $r->title }}</td>
      <td>{{ $r->start_datetime->format('H:i') }}</td>
      <td>{{ $r->end_datetime->format('H:i') }}</td>
      <td class="right muted">{{ $r->break_duration_minutes ?? 0 }} min</td>
      <td class="right bold">{{ $dur }}</td>
      <td><span class="badge badge-{{ $badge }}">{{ ucfirst($r->status) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="10" style="text-align:center;padding:20px;color:#9ca3af;">No records found.</td></tr>
    @endforelse
  </tbody>
</table>
@endsection
