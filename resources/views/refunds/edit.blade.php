@extends('layouts.master')

@section('title', 'Edit Refund Penjualan')

@section('container')
    @include('refunds.partials.form', [
        'boxTitle' => 'Edit Refund Penjualan',
        'formAction' => route('refund.update', $refund),
        'formMethod' => 'PUT',
        'submitLabel' => 'Simpan',
    ])
@endsection

@section('page-script')
    @include('refunds.partials.form-script')
@endsection
