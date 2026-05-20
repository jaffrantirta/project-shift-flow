@extends('reports.pdf.layout')

@section('content')
<table>
  <thead>
    <tr>
      <th>Employee</th>
      <th>Job Title</th>
      <th>Type</th>
      <th>Pay Type</th>
      <th class="right">Rate</th>
      <th>Location</th>
      <th>Period</th>
      <th class="right">Hours</th>
      <th class="right">Est. Cost</th>
    </tr>
  </thead>
  <tbody>
    @php $grandTotal = 0; @endphp
    @forelse($records as $r)
    @php
      $hours   = (float)($r->entries_sum_total_hours ?? 0);
      $rate    = (float)($r->user?->employeeProfile?->pay_rate ?? 0);
      $payType = $r->user?->employeeProfile?->pay_type;
      $cost    = $payType === 'hourly' ? $hours * $rate : $rate;
      $grandTotal += $cost;
    @endphp
    <tr>
      <td class="bold">{{ $r->user?->name ?? '—' }}</td>
      <td class="muted">{{ $r->user?->employeeProfile?->job_title ?? '—' }}</td>
      <td>
        @php
          $empType = $r->user?->employeeProfile?->employment_type;
          $tb = match($empType) { 'full_time'=>'success','part_time'=>'indigo','casual'=>'warning', default=>'gray' };
          $tl = match($empType) { 'full_time'=>'Full Time','part_time'=>'Part Time','casual'=>'Casual','contractor'=>'Contractor', default=>'—' };
        @endphp
        <span class="badge badge-{{ $tb }}">{{ $tl }}</span>
      </td>
      <td><span class="badge badge-gray">{{ ucfirst($payType ?? '—') }}</span></td>
      <td class="right">Rp {{ number_format($rate, 0, ',', '.') }}</td>
      <td><span class="badge badge-indigo">{{ $r->location?->name ?? '—' }}</span></td>
      <td>{{ $r->period_start->format('M j') }} – {{ $r->period_end->format('M j, Y') }}</td>
      <td class="right bold">{{ number_format($hours, 1) }}</td>
      <td class="right bold" style="color:#166534;">Rp {{ number_format($cost, 0, ',', '.') }}</td>
    </tr>
    @empty
    <tr><td colspan="9" style="text-align:center;padding:20px;color:#9ca3af;">No records found.</td></tr>
    @endforelse
  </tbody>
  @if(count($records) > 0)
  <tfoot>
    <tr style="background:#f3f4f6;border-top:2px solid #d1d5db;">
      <td colspan="7" class="bold" style="padding:7px 8px;">TOTAL</td>
      <td class="right bold">{{ number_format($records->sum(fn($r) => (float)($r->entries_sum_total_hours ?? 0)), 1) }} hrs</td>
      <td class="right bold" style="color:#166534;">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
    </tr>
  </tfoot>
  @endif
</table>
@endsection
