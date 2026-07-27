@extends('layouts.master')

@section('title', 'Stock Cabang')

@section('container')
    <section class="content-header">
        <h1>Stock Cabang</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                        <form method="GET" action="{{ route('branch-stock.index') }}" class="form-inline">
                            <div class="form-group">
                                <label>Cabang</label>
                                <select name="owner_id" class="form-control select2" style="min-width:220px" {{ $isBranchScoped ? 'disabled' : '' }}>
                                    <option value="">Semua Cabang</option>
                                    @foreach ($outlets as $outlet)
                                        <option value="{{ $outlet->id }}" {{ (string) $selectedOwnerId === (string) $outlet->id ? 'selected' : '' }}>
                                            {{ $outlet->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if (! $isBranchScoped)
                                <button class="btn btn-primary" type="submit">
                                    <i class="fa fa-filter"></i> Filter
                                </button>
                            @endif
                            <a href="{{ route('branch-stock.kartu', ['owner_id' => $selectedOwnerId]) }}" class="btn btn-default">
                                <i class="fa fa-list"></i> Kartu Stock Cabang
                            </a>
                            <a href="{{ route('branch-stock.opname', ['owner_id' => $selectedOwnerId]) }}" class="btn btn-warning">
                                <i class="fa fa-check-square-o"></i> Opname Cabang
                            </a>
                        </form>
                    </div>

                    <div class="box-body table-responsive">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Cabang</th>
                                    <th>Kode Produk</th>
                                    <th>Produk</th>
                                    <th>Qty</th>
                                    <th>SKU Terakhir</th>
                                    <th>Expired Terdekat</th>
                                    <th>Harga Beli</th>
                                    <th>Nilai</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stocks as $stock)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $stock['owner']?->name ?? '-' }}</td>
                                        <td>{{ $stock['product']?->code ?? '-' }}</td>
                                        <td>{{ $stock['product']?->name ?? '-' }}</td>
                                        <td>{{ $stock['qty_display'] }}</td>
                                        <td>{{ $stock['sku'] ?: '-' }}</td>
                                        <td>{{ $stock['expired_at'] ? $stock['expired_at']->format('d M Y') : '-' }}</td>
                                        <td>@currency($stock['harga_beli'])</td>
                                        <td>@currency($stock['harga_beli'] * $stock['qty'])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
