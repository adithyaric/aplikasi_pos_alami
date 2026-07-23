@extends('layouts.master')

@section('title', 'Tambah Refund Penjualan')

@section('container')
    @include('refunds.partials.form', [
        'boxTitle' => 'Tambah Refund Penjualan',
        'formAction' => route('refund.store'),
        'formMethod' => 'POST',
        'submitLabel' => 'Simpan',
    ])
@endsection

@section('page-script')
    @include('refunds.partials.form-script')
@endsection
