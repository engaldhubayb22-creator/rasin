@extends('layouts.app')
@section('title', __('app.new_project'))
@section('page-title', __('app.new_project'))

@section('content')
<form method="POST" action="{{ route('projects.store') }}">
    @csrf
    @include('projects._form')
    <div class="flex items-center justify-end gap-3 mt-6">
        <a href="{{ route('projects.index') }}" class="text-sm text-slate-500 hover:text-slate-700 px-4 py-2.5">{{ __('app.cancel') }}</a>
        <button type="submit" class="rounded-lg bg-brand-600 hover:bg-brand-700 text-white font-semibold text-sm px-6 py-2.5 transition">{{ __('app.save') }}</button>
    </div>
</form>
@endsection
