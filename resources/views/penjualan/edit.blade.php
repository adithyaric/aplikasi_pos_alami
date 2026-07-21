@extends('layouts.master')

@section('title', 'Edit Penjualan')

@section('container')
    @include('penjualan.partials.warehouse-form', [
        'pageHeading' => 'Penjualan',
        'boxTitle' => 'Edit Penjualan',
        'formAction' => route('penjualan.update', $penjualan),
        'formMethod' => 'PUT',
        'submitLabel' => 'Update',
    ])
@endsection

@section('page-script')
    @include('penjualan.partials.warehouse-form-script')
@endsection
