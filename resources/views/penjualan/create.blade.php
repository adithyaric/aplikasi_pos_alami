@extends('layouts.master')

@section('title', 'Tambah Penjualan')

@section('container')
    @include('penjualan.partials.warehouse-form', [
        'pageHeading' => 'Penjualan',
        'boxTitle' => 'Tambah Penjualan',
        'formAction' => route('penjualan.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'Simpan',
    ])
@endsection

@section('page-script')
    @include('penjualan.partials.warehouse-form-script')
@endsection
