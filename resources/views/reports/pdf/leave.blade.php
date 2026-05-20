@extends('reports.pdf.layout')

@section('content')
<table>
  <thead>
    <tr>
      <th>Employee</th>
      <th>Job Title</th>
      <th>Leave Type</th>
      <th>Paid</th>
      <th>From</th>
      <th>To</th>
      <th class="right">Days</th>
      <th>Status</th>
      <th>Reviewed By</th>
    </tr>
  </thead>
  <tbody>
    @forelse($records as $r)
    <tr>
      <td class="bold">{{ $r->user?->name ?? '—' }}</td>
      <td class="muted">{{ $r->user?->employeeProfile?->job_title ?? '—' }}</td>
      <td><span class="badge badge-indigo">{{ $r->leaveType?->name ?? '—' }}</span></td>
      <td>
        @if($r->leaveType?->is_paid)
          <span class="badge badge-success">Paid</span>
        @else
          <span class="badge badge-gray">Unpaid</span>
        @endif
      </td>
      <td>{{ $r->start_date->format('M j, Y') }}</td>
      <td>{{ $r->end_date->format('M j, Y') }}</td>
      <td class="right bold">{{ number_format((float)$r->total_days, 1) }}</td>
      <td>
        @php
          $badge = match($r->status) { 'approved'=>'success','pending'=>'warning','rejected'=>'danger', default=>'gray' };
        @endphp
        <span class="badge badge-{{ $badge }}">{{ ucfirst($r->status) }}</span>
      </td>
      <td class="muted">{{ $r->reviewedBy?->name ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="9" style="text-align:center;padding:20px;color:#9ca3af;">No records found.</td></tr>
    @endforelse
  </tbody>
</table>
@endsection
