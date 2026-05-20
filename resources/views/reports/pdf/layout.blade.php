<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; background: #fff; }

  /* ── Header ── */
  .header { padding: 18px 24px 14px; border-bottom: 2px solid #4f46e5; display: flex; justify-content: space-between; align-items: flex-end; }
  .brand  { font-size: 18px; font-weight: 700; color: #4f46e5; letter-spacing: -0.3px; }
  .report-meta { text-align: right; }
  .report-title { font-size: 14px; font-weight: 700; color: #111827; }
  .report-sub   { font-size: 9px; color: #6b7280; margin-top: 2px; }

  /* ── Summary bar ── */
  .summary { display: flex; gap: 12px; padding: 10px 24px; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
  .stat { flex: 1; }
  .stat-label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; }
  .stat-value { font-size: 13px; font-weight: 700; color: #4f46e5; margin-top: 1px; }

  /* ── Table ── */
  .table-wrap { padding: 16px 24px; }
  table { width: 100%; border-collapse: collapse; }
  thead tr { background: #4f46e5; }
  thead th { padding: 7px 8px; text-align: left; font-size: 8.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: #fff; white-space: nowrap; }
  thead th.right { text-align: right; }
  tbody tr { border-bottom: 1px solid #f3f4f6; }
  tbody tr:nth-child(even) { background: #f9fafb; }
  tbody td { padding: 6px 8px; font-size: 9px; vertical-align: middle; }
  tbody td.right { text-align: right; }
  tbody td.center { text-align: center; }
  .badge { display: inline-block; padding: 1px 6px; border-radius: 9px; font-size: 8px; font-weight: 600; }
  .badge-success  { background: #dcfce7; color: #166534; }
  .badge-warning  { background: #fef9c3; color: #854d0e; }
  .badge-danger   { background: #fee2e2; color: #991b1b; }
  .badge-indigo   { background: #e0e7ff; color: #3730a3; }
  .badge-gray     { background: #f3f4f6; color: #374151; }
  .bold { font-weight: 700; }
  .muted { color: #9ca3af; }

  /* ── Footer ── */
  .footer { position: fixed; bottom: 0; left: 0; right: 0; padding: 6px 24px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; display: flex; justify-content: space-between; }
</style>
</head>
<body>

<div class="header">
  <div>
    <div class="brand">ShiftFlow</div>
    <div style="font-size:9px;color:#6b7280;margin-top:2px;">Workforce Management</div>
  </div>
  <div class="report-meta">
    <div class="report-title">{{ $title }}</div>
    <div class="report-sub">Generated: {{ now()->format('D, M j Y  H:i') }}</div>
    @if(!empty($subtitle))
    <div class="report-sub">{{ $subtitle }}</div>
    @endif
  </div>
</div>

@if(!empty($stats))
<div class="summary">
  @foreach($stats as $stat)
  <div class="stat">
    <div class="stat-label">{{ $stat['label'] }}</div>
    <div class="stat-value">{{ $stat['value'] }}</div>
  </div>
  @endforeach
</div>
@endif

<div class="table-wrap">
  @yield('content')
</div>

<div class="footer">
  <span>ShiftFlow &copy; {{ date('Y') }}</span>
  <span>Page <span class="pagenum"></span></span>
</div>

</body>
</html>
