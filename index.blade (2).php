@extends('layouts.app')
@section('title', __('app.finance'))
@section('page-title', __('app.finance'))

@section('content')
<div class="mb-5">
    <h2 class="text-xl font-bold text-slate-800">{{ __('app.finance') }}</h2>
    <p class="text-sm text-slate-400">{{ __('app.finance_subtitle') }}</p>
</div>

@include('partials.finance')
@endsection
