@extends('layouts.app')
@section('title', __('app.edit'))
@section('page-title', __('app.edit') . ' · ' . $project->name)

@section('content')
<form method="POST" action="{{ route('projects.update', $project) }}">
    @csrf
    @method('PUT')
    @include('projects._form')
    <div class="flex items-center justify-between gap-3 mt-6">
        <button type="submit" form="delete-form" onclick="return confirm('{{ __('app.confirm_delete') }}')" class="text-sm text-rose-600 hover:text-rose-700 px-4 py-2.5">{{ __('app.delete') }}</button>
        <div class="flex items-center gap-3">
            <a href="{{ route('projects.show', $project) }}" class="text-sm text-slate-500 hover:text-slate-700 px-4 py-2.5">{{ __('app.cancel') }}</a>
            <button type="submit" class="rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm px-6 py-2.5 transition">{{ __('app.save_changes') }}</button>
        </div>
    </div>
</form>

<form id="delete-form" method="POST" action="{{ route('projects.destroy', $project) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endsection
