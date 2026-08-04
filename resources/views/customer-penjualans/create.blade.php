@extends('layouts.master')

@section('title', 'Tambah Customer Penjualan')

@section('container')
    @include('customer-penjualans.form', ['formAction' => route('customer-penjualan.store'), 'formMethod' => 'POST', 'customer' => null, 'selectedType' => old('type', 'toko')])
@endsection
