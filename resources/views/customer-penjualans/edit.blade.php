@extends('layouts.master')

@section('title', 'Edit Customer Penjualan')

@section('container')
    @include('customer-penjualans.form', ['formAction' => route('customer-penjualan.update', [$type, $customer->id]), 'formMethod' => 'PUT', 'customer' => $customer, 'selectedType' => $type])
@endsection
