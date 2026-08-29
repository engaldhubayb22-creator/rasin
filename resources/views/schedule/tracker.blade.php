@extends('layouts.app')
@section('title', __('app.schedule_tracker'))
@section('page-title', __('app.schedule_tracker'))

@section('content')
@php $trkDate = fn ($d) => $d ? $d->format('Y-m-d') : ''; @endphp
<style>
    .trk-wrap { font-family: Tahoma, Arial, sans-serif; }
    .trk-scroll { overflow-x:auto; border:1px solid #90a4ae; border-radius:4px; background:#fff; }
    table.trk { border-collapse:collapse; width:100%; min-width:1650px; font-size:11px; }
    table.trk th { background:#16305c; color:#fff; font-weight:600; padding:7px 6px; white-space:nowrap; border-inline-start:1px solid #24406e; position:sticky; top:0; z-index:2; }
    table.trk th.grp { background:#24406e; }
    table.trk td { padding:2px 4px; border-inline-start:1px solid #eceff1; border-bottom:1px solid #eceff1; white-space:nowrap; }
    table.trk tr.leaf:nth-child(2n) td { background:#fafbfc; }
    table.trk tr.leaf:hover td { background:#fff8e1; }
    table.trk tr.phase td { background:#e8eef3; font-weight:700; color:#16305c; border-top:2px solid #b0bec5; }
    .trk-in { border:1px solid transparent; background:transparent; font-size:11px; padding:2px 4px; border-radius:2px; font-family:inherit; color:#1b2733; width:100%; }
    .trk-in:hover { border-color:#cfd8dc; background:#fff; }
    .trk-in:focus { outline:none; border-color:#1976d2; background:#fff; box-shadow:0 0 0 2px rgba(25,118,210,.15); }
    .trk-in.code { font-family:Consolas,monospace; font-weight:700; color:#16305c; width:52px; }
    .trk-in.num { text-align:center; width:46px; }
    .trk-in.date { font-family:Consolas,monospace; width:112px; }
    .trk-in.pct { text-align:center; width:52px; }
    .trk-in.lag { text-align:center; width:42px; }
    .trk-in.pred { width:64px; font-family:Consolas,monospace; }
    .trk-in.type { width:52px; }
    .trk-in.nm { min-width:180px; text-align:{{ app()->getLocale()==='ar' ? 'right':'left' }}; }
    select.trk-in { appearance:none; -webkit-appearance:none; cursor:pointer; }
    .trk-star { cursor:pointer; font-size:14px; color:#cfd8dc; user-select:none; text-align:center; }
    .trk-star.on { color:#c0392b; }
    .trk-star input { display:none; }
    .trk-status { font-weight:600; }
    .trk-status.s-completed { color:#2e7d32; } .trk-status.s-in_progress { color:#1976d2; }
    .trk-status.s-pending { color:#757575; } .trk-status.s-urgent { color:#e65100; }
    .trk-var { text-align:center; font-weight:700; width:48px; }
    .trk-var.pos { color:#2e7d32; } .trk-var.neg { color:#c0392b; } .trk-var.zero { color:#90a4ae; }
    .trk-del { color:#b0bec5; cursor:pointer; border:none; background:none; }
    .trk-del:hover { color:#c62828; }
    .trk-kpis { display:flex; flex-wrap:wrap; gap:0; background:#fff; border:1px solid #d4d4d4; border-radius:4px; margin-bottom:12px; overflow:hidden; }
    .trk-kpi { flex:1 1 0; min-width:110px; text-align:center; padding:11px; border-inline-start:1px solid #eee; }
    .trk-kpi:first-child { border:none; }
    .trk-kpi b { display:block; font-size:20px; color:#16305c; }
    .trk-kpi span { font-size:11px; color:#7f8c8d; }
    .trk-legend { font-size:11px; color:#78909c; padding:8px 4px; }
</style>

<div class="trk-wrap">
    {{-- ترويسة --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div>
            <a href="{{ route('projects.show', $project) }}" class="text-xs text-brand-600 hover:underline">← {{ $project->name }}</a>
            <h2 class="text-lg font-bold text-slate-800">{{ __('app.schedule_tracker') }}</h2>
            <p class="text-xs text-slate-400">{{ __('app.schedule_tracker_sub') }}</p>
        </div>
        @if ($canEdit)
            <button type="button" onclick="document.getElementById('trk-add').classList.toggle('hidden')" style="background:#16305c;color:#fff;border:none;border-radius:4px;padding:7px 14px;font-size:12.5px;font-weight:600;cursor:pointer">+ {{ __('app.trk_new_activity') }}</button>
        @endif
    </div>

    {{-- المؤشرات --}}
    <div class="trk-kpis">
        <div class="trk-kpi"><b>{{ $kpis['count'] }}</b><span>{{ __('app.trk_activities') }}</span></div>
        <div class="trk-kpi"><b style="color:#2c5490">{{ $kpis['planned'] }}%</b><span>{{ __('app.trk_planned') }}</span></div>
        <div class="trk-kpi"><b style="color:{{ $kpis['variance']<0?'#c0392b':'#2f7d4f' }}">{{ $kpis['actual'] }}%</b><span>{{ __('app.trk_actual') }}</span></div>
        <div class="trk-kpi"><b style="color:{{ $kpis['variance']<0?'#c0392b':'#2f7d4f' }}">{{ $kpis['variance']>=0?'+':'' }}{{ $kpis['variance'] }}%</b><span>{{ __('app.trk_variance') }}</span></div>
        <div class="trk-kpi"><b style="color:#c0392b">{{ $kpis['critical'] }}</b><span>{{ __('app.trk_critical') }} ★</span></div>
        <div class="trk-kpi"><b style="color:#2f7d4f">{{ $kpis['completed'] }}</b><span>{{ __('app.trk_completed') }}</span></div>
        <div class="trk-kpi"><b style="color:{{ $kpis['delayed']?'#c0392b':'#2f7d4f' }}">{{ $kpis['delayed'] }}</b><span>{{ __('app.trk_delayed') }}</span></div>
    </div>

    {{-- نموذج إضافة نشاط --}}
    @if ($canEdit)
    <div id="trk-add" class="hidden bg-white rounded border border-slate-300 p-4 mb-3">
        <form method="POST" action="{{ route('activities.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end text-[13px]">@csrf
            <div class="sm:col-span-1"><label class="block text-xs text-slate-500 mb-1">{{ __('app.trk_id') }}</label><input name="activity_code" class="w-full rounded border border-slate-300 px-2 py-1.5" style="font-family:Consolas,monospace"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.phase') }}</label><input name="phase" list="trk-phases" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_item') }} (AR) *</label><input name="name" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.trk_duration') }}</label><input name="duration_days" type="number" min="0" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.status') }}</label>
                <select name="status" class="w-full rounded border border-slate-300 px-2 py-1.5">@foreach ($statuses as $k => $lbl)<option value="{{ $k }}">{{ __('app.'.$lbl) }}</option>@endforeach</select></div>
            <div class="sm:col-span-6"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_item') }} (EN)</label><input name="name_en" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.trk_level') }}</label><input name="level" type="number" min="0" max="5" value="2" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-12"><button style="background:#16305c;color:#fff;border:none;border-radius:4px;padding:7px 16px;font-size:13px;font-weight:600;cursor:pointer">{{ __('app.add') }}</button></div>
        </form>
        <datalist id="trk-phases">@foreach ($grouped->keys() as $ph)<option value="{{ $ph }}">@endforeach</datalist>
    </div>
    @endif

    {{-- شبكة الجدول --}}
    <div class="trk-scroll">
        <table class="trk">
            <thead>
                <tr>
                    <th>{{ __('app.trk_id') }}</th>
                    <th>{{ __('app.trk_activity_en') }}</th>
                    <th>{{ __('app.trk_activity_ar') }}</th>
                    <th>{{ __('app.trk_dur') }}</th>
                    <th>{{ __('app.trk_start') }}</th>
                    <th>{{ __('app.trk_finish') }}</th>
                    <th>★</th>
                    <th>{{ __('app.trk_planned_pct') }}</th>
                    <th>{{ __('app.trk_actual_pct') }}</th>
                    <th>{{ __('app.trk_variance') }}</th>
                    <th>{{ __('app.status') }}</th>
                    <th>{{ __('app.trk_level') }}</th>
                    <th class="grp">{{ __('app.trk_pred') }} 1</th><th class="grp">{{ __('app.trk_type') }}</th><th class="grp">{{ __('app.trk_lag') }}</th>
                    <th class="grp">{{ __('app.trk_pred') }} 2</th><th class="grp">{{ __('app.trk_type') }}</th><th class="grp">{{ __('app.trk_lag') }}</th>
                    <th class="grp">{{ __('app.trk_pred') }} 3</th><th class="grp">{{ __('app.trk_type') }}</th><th class="grp">{{ __('app.trk_lag') }}</th>
                    @if ($canEdit)<th></th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse ($grouped as $phase => $items)
                    <tr class="phase"><td colspan="{{ $canEdit ? 22 : 21 }}">{{ $phase ?: '—' }}</td></tr>
                    @foreach ($items as $a)
                        @php $ro = $canEdit ? '' : 'readonly'; $dis = $canEdit ? '' : 'disabled'; $v = $a->variance(); @endphp
                        <tr class="leaf" data-id="{{ $a->id }}" data-action="{{ route('activities.update', $a) }}">
                            <td><input class="trk-in code" name="activity_code" value="{{ $a->activity_code }}" {{ $ro }}></td>
                            <td><input class="trk-in nm" name="name_en" value="{{ $a->name_en }}" {{ $ro }}></td>
                            <td><input class="trk-in nm" name="name" value="{{ $a->name }}" {{ $ro }} dir="rtl"></td>
                            <td><input class="trk-in num" name="duration_days" type="number" min="0" value="{{ $a->duration_days }}" {{ $ro }}></td>
                            <td><input class="trk-in date" name="planned_start" type="date" value="{{ $trkDate($a->planned_start) }}" {{ $ro }}></td>
                            <td><input class="trk-in date" name="planned_finish" type="date" value="{{ $trkDate($a->planned_finish) }}" {{ $ro }}></td>
                            <td><label class="trk-star {{ $a->is_critical ? 'on' : '' }}">★<input type="checkbox" name="is_critical" @checked($a->is_critical) {{ $dis }}></label></td>
                            <td><input class="trk-in pct" name="planned_percent" type="number" min="0" max="100" value="{{ $a->planned_percent }}" {{ $ro }}></td>
                            <td><input class="trk-in pct" name="actual_percent" type="number" min="0" max="100" value="{{ $a->actual_percent }}" {{ $ro }}></td>
                            <td class="trk-var {{ $v>0?'pos':($v<0?'neg':'zero') }}" data-var>{{ $v>0?'+':'' }}{{ $v }}</td>
                            <td><select class="trk-in trk-status {{ $a->statusClass() }}" name="status" {{ $dis }}>@foreach ($statuses as $k => $lbl)<option value="{{ $k }}" @selected($a->status===$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select></td>
                            <td><input class="trk-in num" name="level" type="number" min="0" max="5" value="{{ $a->level }}" {{ $ro }}></td>
                            @foreach ([1,2,3] as $i)
                                <td><select class="trk-in pred" name="pred{{ $i }}" {{ $dis }}><option value="">—</option>@foreach ($codes as $c)<option value="{{ $c['code'] }}" @selected($a->{'pred'.$i}===$c['code'])>{{ $c['code'] }}</option>@endforeach</select></td>
                                <td><select class="trk-in type" name="type{{ $i }}" {{ $dis }}><option value="">—</option>@foreach ($relTypes as $t)<option value="{{ $t }}" @selected($a->{'type'.$i}===$t)>{{ $t }}</option>@endforeach</select></td>
                                <td><input class="trk-in lag" name="lag{{ $i }}" type="number" value="{{ $a->{'lag'.$i} }}" {{ $ro }}></td>
                            @endforeach
                            @if ($canEdit)
                                <td><form method="POST" action="{{ route('activities.destroy', $a) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')<button class="trk-del" title="{{ __('app.delete') }}">✕</button></form></td>
                            @endif
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="{{ $canEdit ? 22 : 21 }}" style="text-align:center;padding:40px;color:#90a4ae">{{ __('app.trk_empty') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="trk-legend">★ = {{ __('app.trk_on_critical_path') }} · {{ __('app.trk_types_note') }} · {{ __('app.trk_autosave_note') }}</div>
</div>

@push('scripts')
<script>
(function () {
    if (!{{ $canEdit ? 'true' : 'false' }}) return;
    var meta = document.querySelector('meta[name=csrf-token]');
    function saveRow(row) {
        var fd = new FormData();
        fd.append('_method', 'PATCH');
        row.querySelectorAll('.trk-in, .trk-star input').forEach(function (el) {
            if (el.type === 'checkbox') { fd.append(el.name, el.checked ? '1' : '0'); }
            else if (el.name) { fd.append(el.name, el.value); }
        });
        fetch(row.dataset.action, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': meta ? meta.content : '' },
            body: fd
        })
        .then(function (r) { if (!r.ok) throw 0; return r.json(); })
        .then(function (d) {
            // تحديث لون الحالة
            var sel = row.querySelector('select[name=status]');
            if (sel && d.status_class) { sel.className = sel.className.replace(/\bs-[a-z_]+\b/g, '').replace(/\s+/g, ' ').trim(); sel.classList.add('trk-in','trk-status', d.status_class); }
            // تحديث الانحراف
            var vc = row.querySelector('[data-var]');
            if (vc && typeof d.variance !== 'undefined') { var v = d.variance; vc.textContent = (v > 0 ? '+' : '') + v; vc.className = 'trk-var ' + (v > 0 ? 'pos' : (v < 0 ? 'neg' : 'zero')); }
            if (window.acnToast) window.acnToast(@json(__('app.saved')), true);
        })
        .catch(function () { if (window.acnToast) window.acnToast(@json(__('app.save_failed')), false); });
    }
    document.querySelectorAll('tr.leaf').forEach(function (row) {
        row.querySelectorAll('.trk-in, .trk-star input').forEach(function (el) {
            el.addEventListener('change', function () {
                if (el.classList.contains('trk-star') || el.type === 'checkbox') {
                    var lbl = el.closest('.trk-star'); if (lbl) lbl.classList.toggle('on', el.checked);
                }
                saveRow(row);
            });
        });
    });
})();
</script>
@endpush
@endsection
