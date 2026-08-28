@extends('layouts.app')
@section('title', __('app.users'))
@section('page-title', __('app.users_roles'))

@section('content')
@include('partials.acn-styles')
<style>
    .usr .acn-thead, .usr .acn-row { display:grid; grid-template-columns:minmax(160px,1fr) 170px 200px 90px 40px; align-items:center; }
    @media (max-width:800px){ .usr .acn-thead,.usr .acn-row{ grid-template-columns:1fr 140px 80px 40px } .usr .acn-thead>:nth-child(3),.usr .acn-row>:nth-child(3){display:none} }
    .pm-tbl { width:100%; border-collapse:collapse; background:#fff; }
    .pm-tbl th, .pm-tbl td { border:1px solid #eceff1; padding:7px 9px; font-size:12px; text-align:center; }
    .pm-tbl th { background:#e8eef3; color:#2c3e50; font-weight:600; font-size:11px; }
    .pm-tbl th.perm, .pm-tbl td.perm { text-align:{{ app()->getLocale()==='ar' ? 'right' : 'left' }}; }
    .pm-tbl td.perm code { font-family:Consolas,monospace; font-size:11px; color:#607d8b; }
    .pm-mod td { background:#f5f8fc; font-weight:700; color:#1a365d; text-align:{{ app()->getLocale()==='ar' ? 'right' : 'left' }}; font-size:12px; }
    .pm-tbl input[type=checkbox] { width:16px; height:16px; cursor:pointer; accent-color:#1976d2; }
    .pm-tbl input:disabled { accent-color:#90a4ae; cursor:not-allowed; }
    .usr-active { display:inline-flex; align-items:center; gap:5px; font-size:11px; }
    .usr-active input { width:15px; height:15px; accent-color:#2e7d32; }
</style>

<div class="acn-wrap">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div>
            <h2 class="text-lg font-bold text-slate-800">{{ __('app.users_roles') }}</h2>
            <p class="text-xs text-slate-400">{{ __('app.users_roles_sub') }}</p>
        </div>
        <button type="button" onclick="document.getElementById('usr-add').classList.toggle('hidden')" class="acn-btn-primary">+ {{ __('app.usr_new') }}</button>
    </div>

    {{-- نموذج مستخدم جديد --}}
    <div id="usr-add" class="hidden bg-white rounded border border-slate-300 p-4 mb-3">
        <form method="POST" action="{{ route('users.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end text-[13px]">@csrf
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.usr_name') }} *</label><input name="name" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-3"><label class="block text-xs text-slate-500 mb-1">{{ __('app.usr_email') }} *</label><input name="email" type="email" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.usr_phone') }}</label><input name="phone" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.usr_role') }} *</label>
                <select name="role" class="w-full rounded border border-slate-300 px-2 py-1.5">@foreach ($roles as $k => $lbl)<option value="{{ $k }}">{{ __('app.'.$lbl) }}</option>@endforeach</select></div>
            <div class="sm:col-span-2"><label class="block text-xs text-slate-500 mb-1">{{ __('app.usr_password') }}</label><input name="password" type="text" placeholder="Rasine#2026" class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-12"><button class="acn-btn-primary">{{ __('app.add') }}</button><span class="text-xs text-slate-400 mx-3">{{ __('app.usr_default_pass_hint') }}</span></div>
        </form>
    </div>

    {{-- جدول المستخدمين --}}
    <div class="acn-table usr">
        <div class="acn-thead">
            <div>{{ __('app.usr_name') }}</div>
            <div>{{ __('app.usr_role') }}</div>
            <div>{{ __('app.usr_email') }}</div>
            <div>{{ __('app.usr_status') }}</div>
            <div></div>
        </div>
        @foreach ($users as $u)
            <div class="acn-row">
                <div class="acn-title-cell"><div class="t">{{ $u->name }}</div>@if ($u->job_title)<div class="acn-notes">{{ $u->job_title }}</div>@endif</div>
                <div>
                    <form method="POST" action="{{ route('users.update', $u) }}" class="acn-ajax">@csrf @method('PATCH')
                        <select name="role" class="acn-inline-select" onchange="this.form.requestSubmit()">
                            @foreach ($roles as $k => $lbl)<option value="{{ $k }}" @selected($u->role===$k)>{{ __('app.'.$lbl) }}</option>@endforeach</select>
                    </form>
                </div>
                <div class="text-slate-500" style="font-size:11px">{{ $u->email }}</div>
                <div>
                    <form method="POST" action="{{ route('users.update', $u) }}" class="acn-ajax">@csrf @method('PATCH')
                        <input type="hidden" name="is_active" value="0">
                        <label class="usr-active {{ $u->is_active ? 'text-emerald-700' : 'text-slate-400' }}">
                            <input type="checkbox" name="is_active" value="1" @checked($u->is_active) onchange="this.form.requestSubmit()">
                            {{ $u->is_active ? __('app.usr_on') : __('app.usr_off') }}
                        </label>
                    </form>
                </div>
                <div>
                    @if ($u->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $u) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                            <button class="acn-del" title="{{ __('app.delete') }}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg></button>
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- مصفوفة الأدوار والصلاحيات --}}
    <div class="flex items-center gap-2 mt-6 mb-2">
        <h3 class="text-base font-bold text-slate-800">{{ __('app.roles_permissions') }}</h3>
        <span class="text-xs text-slate-400">— {{ __('app.roles_permissions_sub') }}</span>
    </div>
    <div class="acn-table" style="overflow-x:auto">
        <table class="pm-tbl" id="pm-matrix">
            <thead><tr>
                <th class="perm">{{ __('app.permission') }}</th>
                @foreach ($roles as $rk => $rlbl)<th>{{ __('app.'.$rlbl) }}</th>@endforeach
            </tr></thead>
            <tbody>
            @foreach ($modules as $module => $actions)
                <tr class="pm-mod"><td colspan="{{ count($roles) + 1 }}">{{ __('app.perm_mod_'.$module) }}</td></tr>
                @foreach ($actions as $action)
                    @php $perm = $module.'.'.$action; @endphp
                    <tr>
                        <td class="perm">{{ __('app.perm_act_'.$action) }} <code>{{ $perm }}</code></td>
                        @foreach ($roles as $rk => $rlbl)
                            @php
                                $isAdmin = $rk === \App\Models\User::ROLE_ADMIN;
                                $checked = $isAdmin || in_array($perm, $rolePerms[$rk] ?? [], true);
                            @endphp
                            <td>
                                <input type="checkbox" class="pm-chk" data-role="{{ $rk }}" data-perm="{{ $perm }}"
                                    @checked($checked) @disabled($isAdmin)>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-slate-400 mt-2">{{ __('app.perm_admin_note') }}</p>
</div>

@push('scripts')
<script>
(function(){
    var meta = document.querySelector('meta[name=csrf-token]');
    var url = @json(route('role-permissions.toggle'));
    document.querySelectorAll('.pm-chk:not(:disabled)').forEach(function(chk){
        chk.addEventListener('change', function(){
            var body = new FormData();
            body.append('role', chk.dataset.role);
            body.append('permission', chk.dataset.perm);
            body.append('granted', chk.checked ? '1' : '0');
            fetch(url, { method:'POST', headers:{ 'X-Requested-With':'XMLHttpRequest','Accept':'application/json','X-CSRF-TOKEN': meta?meta.content:'' }, body: body })
                .then(function(r){ if(!r.ok) throw 0; return r.json(); })
                .then(function(){ if(window.acnToast) window.acnToast(@json(__('app.saved')), true); })
                .catch(function(){ chk.checked = !chk.checked; if(window.acnToast) window.acnToast(@json(__('app.save_failed')), false); });
        });
    });
})();
</script>
@endpush
@endsection
