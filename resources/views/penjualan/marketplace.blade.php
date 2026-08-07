@extends('layouts.master')

@section('title', 'Penjualan')

@section('container')
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Data Penjualan
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <td>No</td>
                                    <td>Kode Invoice</td>
                                    <td>Customer</td>
                                    <td>Kas/Metode Pembayaran</td>
                                    <td>Cabang</td>
                                    <td>Kasir</td>
                                    <td>Detail</td>
                                    <td>Aksi</td>
                                </tr>
                            </thead>
                            @foreach ($penjualan as $value)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $value->code }}</td>
                                    <td>{{ $value->customer->name }}</td>
                                    <td>{{ $value->kas?->name ?? $value->transaction?->payment?->name }}</td>
                                    <td>{{ $value->outlet->name ?? '___customer' }}</td>
                                    <td>{{ $value->kasir->name ?? '___customer' }}</td>
                                    <td>
                                        <table class="table table-sm table-bordered">
                                            <tr>
                                                <th>Product</th>
                                                <th>Banyak</th>
                                                <th>Diskon / Item</th>
                                                <th>Harga Jual</th>
                                                <th>Sub total</th>
                                            </tr>
                                            @php
                                                $totalCost = 0;
                                                $totalDiscount = (int) ($value->discount ?? 0);
                                            @endphp
                                            @foreach ($value->items as $item)
                                                @php
                                                    $itemDiscount = (int) ($item->discount ?? 0);
                                                    $lineSubtotal = (int) ($item->subtotal ?? ($item->qty * $item->price));
                                                    $totalCost += $lineSubtotal + $itemDiscount;
                                                    $totalDiscount += $itemDiscount;
                                                @endphp
                                                <tr>
                                                    <td>{{ $item->product->name }}</td>
                                                    <td>{{ $item->qty }}</td>
                                                    <td>@currency($itemDiscount)</td>
                                                    <td>@currency($item->price)</td>
                                                    <td>@currency($lineSubtotal)</td>
                                                </tr>
                                            @endforeach
                                            <tr>
                                                <th colspan="2">Diskon : @currency($totalDiscount)</th>
                                                <th colspan="3" class="text-right">Total : @currency($totalCost)</th>
                                            </tr>
                                            <tr>
                                                <th colspan="5" class="text-right">Grand Total : @currency($totalCost - $totalDiscount)</th>
                                            </tr>
                                        </table>
                                    </td>
                                    <td>
                                        <a class="btn btn-info" href="{{ route('penjualan.show', $value->id) }}">Show</a>
                                        <a class="btn btn-warning" href="{{ route('laporan.penjualan.invoice', $value->id) }}">Invoice XLSX</a>
                                        <form action="{{ route('penjualan.destroy', $value->id) }}" method="post"
                                            style="display: inline;">
                                            @method('delete')
                                            @csrf
                                            <button class="border-0 btn btn-danger"
                                                onclick="return confirm('Are you sure?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </table>
                    </div><!-- /.box-body -->
                </div><!-- /.box -->
            </div><!-- /.col -->
        </div><!-- /.row -->
    </section><!-- /.content -->
@endsection
