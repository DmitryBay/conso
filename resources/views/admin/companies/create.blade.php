@extends('layouts.admin')
@section('title', 'Новая компания')
@section('content')
<div class="mb-4"><a class="text-secondary small" href="{{ route('platform.companies.index') }}"><i class="bi bi-arrow-left me-1"></i> Компании</a><div class="eyebrow mt-3">Подключение клиента</div><h1 class="page-title">Новая компания</h1><p class="page-subtitle">Создайте пространство отеля и сразу назначьте владельца.</p></div>
@if($errors->any())<div class="alert alert-danger"><strong>Проверьте заполнение формы.</strong><div class="mt-1">Некоторые поля заполнены неверно.</div></div>@endif
<form method="POST" action="{{ route('platform.companies.store') }}">@csrf @include('admin.companies._form')</form>
@endsection
