@extends('layouts.app')
@section('title', __('app.admin_checklist_template'))
@section('page-title', __('app.administration'))

@section('content')
@include('partials.acn-styles')
<style>
    .tpl .acn-thead, .tpl .acn-row { display:grid; grid-template-columns:32px 150px minmax(240px,1fr) 130px 44px; align-items:center; }
    @media (max-width:800px){ .tpl .acn-thead,.tpl .acn-row{ grid-template-columns:1fr 110px 40px } .tpl .acn-thead>:nth-child(1),.tpl .acn-thead>:nth-child(2),.tpl .acn-row>:nth-child(1),.tpl .acn-row>:nth-child(2){display:none} }
</style>

<div class="acn-wrap">
    {{-- الترويسة --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div>
            <h2 class="text-lg font-bold text-slate-800">{{ __('app.admin_checklist_template') }}</h2>
            <p class="text-xs text-slate-400">{{ __('app.admin_checklist_template_sub') }}</p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="document.getElementById('tpl-add').classList.toggle('hidden')" class="acn-btn-primary">+ {{ __('app.tpl_new_item') }}</button>
            <form method="POST" action="{{ route('admin.checklist-template.reset') }}" onsubmit="return confirm('{{ __('app.tpl_reset_confirm') }}')">@csrf
                <button class="acn-btn-ghost">↻ {{ __('app.tpl_reset') }}</button>
            </form>
        </div>
    </div>

    {{-- تنبيه توضيحي --}}
    <div class="mb-3 flex items-start gap-2 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 text-[13px] leading-relaxed">
        <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>{{ __('app.tpl_notice') }}</span>
    </div>

    {{-- نموذج إضافة بند --}}
    <div id="tpl-add" class="hidden bg-white rounded border border-slate-300 p-4 mb-3">
        <form method="POST" action="{{ route('admin.checklist-template.store') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-end text-[13px]">
            @csrf
            <div class="sm:col-span-4"><label class="block text-xs text-slate-500 mb-1">{{ __('app.phase') }} *</label>
                <input name="phase" list="tpl-phases" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-6"><label class="block text-xs text-slate-500 mb-1">{{ __('app.req_item') }} *</label>
                <input name="title" required class="w-full rounded border border-slate-300 px-2 py-1.5"></div>
            <div class="sm:col-span-2 flex items-center h-[34px]"><label class="flex items-center gap-1.5 text-xs text-slate-600"><input type="checkbox" name="is_mandatory" value="1" checked class="rounded border-slate-300">{{ __('app.cl_mandatory') }}</label></div>
            <div class="sm:col-span-12"><button class="acn-btn-primary">{{ __('app.add') }}</button></div>
        </form>
        <datalist id="tpl-phases">@foreach ($phases as $ph)<option value="{{ $ph }}">@endforeach</datalist>
    </div>

    {{-- الجدول مجمّع حسب المرحلة --}}
    @if ($total)
        <div class="acn-table tpl">
            <div class="acn-thead">
                <div></div>
                <div>{{ __('app.phase') }}</div>
                <div>{{ __('app.req_item') }}</div>
                <div>{{ __('app.cl_mandatory') }}</div>
                <div></div>
            </div>

            @foreach ($grouped as $phase => $items)
                <div class="acn-project-bar">
                    <span><strong>{{ $phase }}</strong></span>
                    <span class="acn-meta">{{ $items->count() }} {{ __('app.tpl_items_count') }}</span>
                </div>
                @foreach ($items as $it)
                    <div class="acn-row">
                        <div class="text-slate-400 text-center" style="font-size:11px">•</div>
                        <div class="text-slate-500" style="font-size:11px">
                            <form method="POST" action="{{ route('admin.checklist-template.update', $it) }}" class="acn-ajax">@csrf @method('PATCH')
                                <input name="phase" value="{{ $it->phase }}" class="acn-inline-input" onchange="this.form.requestSubmit()"></form>
                        </div>
                        <div class="acn-title-cell">
                            <form method="POST" action="{{ route('admin.checklist-template.update', $it) }}" class="acn-ajax">@csrf @method('PATCH')
                                <input name="title" value="{{ $it->title }}" class="acn-inline-input" style="font-weight:600" onchange="this.form.requestSubmit()"></form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('admin.checklist-template.update', $it) }}" class="acn-ajax">@csrf @method('PATCH')
                                <input type="hidden" name="is_mandatory" value="0">
                                <label class="flex items-center gap-1.5 text-xs {{ $it->is_mandatory ? 'text-emerald-700' : 'text-slate-400' }}">
                                    <input type="checkbox" name="is_mandatory" value="1" @checked($it->is_mandatory) onchange="this.form.requestSubmit()" class="rounded border-slate-300">
                                    {{ $it->is_mandatory ? __('app.cl_mandatory') : __('app.cl_optional') }}
                                </label>
                            </form>
                        </div>
                        <div>
                            <form method="POST" action="{{ route('admin.checklist-template.destroy', $it) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">@csrf @method('DELETE')
                                <button class="acn-del" title="{{ __('app.delete') }}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 7h12M9 7V5h6v2m-7 0l1 12h6l1-12"/></svg></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @else
        <div class="acn-empty">{{ __('app.no_checklist_items') }}</div>
    @endif
</div>
@endsection
