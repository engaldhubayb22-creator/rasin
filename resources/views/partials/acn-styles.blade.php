@php $acnRtl = app()->getLocale() === 'ar'; @endphp
{{-- ستايل جداول أكونكس المشترك --}}
<style>
    .acn-wrap { font-family: Tahoma, Arial, sans-serif; }
    .acn-btn-primary { background:#1976d2; color:#fff; border:1px solid #1565c0; border-radius:2px; padding:6px 14px; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-block; }
    .acn-btn-primary:hover { background:#1565c0; }
    .acn-btn-ghost { background:#fff; border:1px solid #c0c4c8; border-radius:2px; padding:6px 12px; font-size:12px; color:#455a64; cursor:pointer; text-decoration:none; }
    .acn-btn-ghost:hover { background:#eef0f2; }
    .acn-kpis { display:flex; background:#fff; border:1px solid #d4d4d4; border-radius:4px; margin-bottom:12px; overflow:hidden; flex-wrap:wrap; }
    .acn-kpi { padding:12px 20px; border-inline-start:1px solid #e8e8e8; flex:1 1 0; text-align:center; min-width:120px; }
    .acn-kpi:first-child { border-inline-start:none; }
    .acn-kpi-value { font-size:22px; font-weight:700; color:#2c3e50; line-height:1; }
    .acn-kpi-label { font-size:11px; color:#7f8c8d; margin-top:5px; }
    .acn-kpi.danger .acn-kpi-value { color:#c0392b; }
    .acn-kpi.warning .acn-kpi-value { color:#d68910; }
    .acn-kpi.info .acn-kpi-value { color:#2874a6; }
    .acn-kpi.success .acn-kpi-value { color:#1e7e34; }
    .acn-toolbar { display:flex; gap:6px; padding:10px 14px; background:#f5f6f7; border:1px solid #d4d4d4; border-radius:4px; margin-bottom:12px; flex-wrap:wrap; align-items:center; }
    .acn-toolbar select { padding:5px 8px; border:1px solid #c0c4c8; border-radius:2px; font-size:12px; background:#fff; min-width:130px; font-family:inherit; }
    .acn-clear { color:#94a3b8; font-size:12px; text-decoration:none; padding:0 6px; }
    .acn-clear:hover { color:#c0392b; }
    .acn-table { background:#fff; border:1px solid #b0bec5; border-radius:4px; overflow:hidden; margin-bottom:14px; }
    .acn-thead > div, .acn-row > div { padding:6px 10px; border-inline-start:1px solid #f0f3f5; overflow:hidden; text-overflow:ellipsis; text-align:{{ $acnRtl ? 'right' : 'left' }}; }
    .acn-thead { background:#e8eef3; font-size:11px; font-weight:600; color:#2c3e50; border-bottom:2px solid #90a4ae; }
    .acn-thead > div { border-inline-start:1px solid #cfd8dc; padding:8px 10px; }
    .acn-thead > div:first-child, .acn-row > div:first-child { border-inline-start:none; text-align:center; }
    .acn-project-bar { background:#e8eef3; padding:8px 12px; color:#2c3e50; font-size:12px; display:flex; align-items:center; gap:12px; border-bottom:1px solid #b0bec5; flex-wrap:wrap; }
    .acn-project-bar strong { color:#1a365d; font-weight:600; margin-inline-end:4px; }
    .acn-project-bar .acn-meta { color:#607d8b; font-size:11px; }
    .acn-overdue { color:#d32f2f; font-weight:600; font-size:11px; }
    .acn-row { font-size:12px; border-bottom:1px solid #eceff1; color:#2c3e50; }
    .acn-row:nth-child(2n) { background:#fafbfc; }
    .acn-row:hover { background:#fff8e1 !important; }
    .acn-code { font-family:Consolas,"Courier New",monospace; font-size:11px; color:#546e7a; font-weight:600; }
    .acn-title-cell .t { color:#0f172a; font-weight:500; font-size:12px; line-height:1.4; }
    .acn-notes { font-size:10px; color:#78909c; line-height:1.3; margin-top:2px; }
    .acn-badge-req { display:inline-block; font-size:9px; font-weight:700; padding:1px 5px; border-radius:2px; background:#fdecea; color:#c0392b; margin-inline-start:6px; }
    .acn-badge-opt { display:inline-block; font-size:9px; font-weight:600; padding:1px 5px; border-radius:2px; background:#eef1f3; color:#78909c; margin-inline-start:6px; }
    .acn-inline-select, .acn-inline-date { width:100%; padding:3px 6px; border:1px solid transparent; background:transparent; font-size:12px; color:#2c3e50; cursor:pointer; border-radius:2px; font-family:inherit; text-align:{{ $acnRtl ? 'right' : 'left' }}; }
    .acn-inline-select { appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'><path d='M0 2 L4 6 L8 2' fill='none' stroke='%2378909c' stroke-width='1.5'/></svg>"); background-repeat:no-repeat; background-position:{{ $acnRtl ? 'left' : 'right' }} 6px center; padding-{{ $acnRtl ? 'left' : 'right' }}:18px; }
    .acn-inline-date { font-family:Consolas,monospace; }
    .acn-inline-date.overdue { color:#c62828; font-weight:600; }
    .acn-inline-input { width:100%; padding:3px 6px; border:1px solid transparent; background:transparent; font-size:12px; color:#2c3e50; border-radius:2px; font-family:inherit; }
    .acn-inline-select:hover, .acn-inline-date:hover, .acn-inline-input:hover { border-color:#b0bec5; background-color:#fff; }
    .acn-inline-select:focus, .acn-inline-date:focus, .acn-inline-input:focus { outline:none; border-color:#1976d2; background-color:#fff; box-shadow:0 0 0 2px rgba(25,118,210,.15); }
    .acn-status-select.s-completed { color:#2e7d32; font-weight:600; }
    .acn-status-select.s-in_progress { color:#1976d2; font-weight:500; }
    .acn-status-select.s-pending { color:#757575; font-weight:500; }
    .acn-status-select.s-urgent { color:#e65100; font-weight:700; }
    .acn-status-select.s-cancelled { color:#9e9e9e; text-decoration:line-through; }
    .acn-del { color:#78909c; background:none; border:none; cursor:pointer; padding:2px; }
    .acn-del:hover { color:#c62828; }
    .acn-empty { padding:48px 20px; text-align:center; color:#78909c; background:#fff; border:1px solid #d4d4d4; border-radius:4px; }
</style>
