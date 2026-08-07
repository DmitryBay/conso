@extends('layouts.admin')
@section('title', 'Редактирование компании')
@section('content')
<div class="mb-4"><a class="text-secondary small" href="{{ route('platform.companies.show',$company) }}"><i class="bi bi-arrow-left me-1"></i> {{ $company->name }}</a><div class="eyebrow mt-3">Настройки аккаунта</div><h1 class="page-title">Редактирование</h1><p class="page-subtitle">Обновите компанию и данные её владельца.</p></div>
@if($errors->any())<div class="alert alert-danger"><strong>Проверьте заполнение формы.</strong><div class="mt-1">Некоторые поля заполнены неверно.</div></div>@endif
<form method="POST" action="{{ route('platform.companies.update',$company) }}">@csrf @method('PUT') @include('admin.companies._form')</form>
@endsection
