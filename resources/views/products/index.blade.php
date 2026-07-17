@inject('carbon', 'Carbon\Carbon')

@extends('layouts.master')

@section('title', 'Products')

@section('container')
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        Data Products
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header">
                    <a href="{{ route('product.create') }}" class="btn btn-sm bg-light-blue"><i class="fa fa-plus"></i>Tambah</a>
                    <a href="{{ route('product.export') }}" class="btn btn-sm bg-green">
                        <i class="fa fa-download"></i> Export
                    </a>
                    <form method="GET" action="{{ route('product.index') }}" class="row" style="margin-top:10px;">
                        <div class="col-xs-12" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                            <input type="text" name="search" value="{{ $search }}" class="form-control input-sm"
                                style="width:220px;" placeholder="Cari Produk">
{{--
                            <select name="category_id" id="filterKategori" class="form-control input-sm select2"
                                style="width:auto; min-width:180px;">
                                <option value="">Semua Kategori</option>
                                @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) $selectedCategoryId === (string) $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                                @endforeach
                            </select>  --}}
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa fa-search"></i> Filter
                            </button>
                            <a href="{{ route('product.index') }}" class="btn btn-sm btn-default">Reset</a>
                        </div>
                    </form>
                </div><!-- /.box-header -->
                <div class="box-body table-responsive text-nowrap">
                    <table id="products-table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <td>No</td>
                                <td>Barcode</td>
                                <td>Nama</td>
                                <td style="display: none;">Kategori</td>
                                {{--  <td>Satuan</td>  --}}
                                {{--  <td>Satuan Besar</td>  --}}
                                <td>Konversi</td>
                                <td>Harga Beli</td>
                                <td>Aksi</td>
                                <td style="display:none;">Lokasi</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($products as $value)
                            <tr>
                                <td>{{ ($products->firstItem() ?? 1) + $loop->index }}</td>
                                <td>{{ $value->code }}</td>
                                <td>{{ $value->name }}</td>
                                <td style="display: none;">{{ $value->category?->name }}</td>
                                <td>
                                    {{ $value->konversi_string }}
                                    @if($value->satuan_terbesar && $value->konversi_qty_terbesar)
                                        <br>
                                        {{ $value->konversi_terbesar_string }}
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-xs btn-info btn-price-history"
                                        data-toggle="modal" data-target="#priceHistoryModal"
                                        data-id="{{ $value->id }}">
                                        @currency($value->harga_beli)
                                    </button>
                                </td>
                                <td>
                                    <a class="btn btn-warning"
                                        href="{{ route('product.edit', $value->id) }}">Edit</a>
                                    <form action="{{ route('product.destroy', $value->id) }}" method="post"
                                        style="display: inline;">
                                        @method('delete')
                                        @csrf
                                        <button class="border-0 btn btn-danger"
                                            onclick="return confirm('Are you sure?')">Hapus</button>
                                    </form>
                                </td>
                                <td style="display:none;">{{ $value->lokasi ?? '' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Price History Modal -->
                    <div class="modal fade" id="priceHistoryModal" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                    <h4 class="modal-title">Price History (Harga Beli)</h4>
                                </div>
                                <div class="modal-body">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>User</th>
                                                <th>Change</th>
                                            </tr>
                                        </thead>
                                        <tbody id="priceHistoryBody">
                                            <tr>
                                                <td colspan="3" class="text-center">Loading...</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        {{ $products->links() }}
                    </div>
                </div><!-- /.box-body -->
            </div><!-- /.box -->
        </div><!-- /.col -->
    </div><!-- /.row -->
</section><!-- /.content -->
@endsection
@section('page-script')
<script>
    // Price history modal (unchanged)
    $('#priceHistoryModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var modal = $(this);
        modal.find('#priceHistoryBody').html(
            '<tr><td colspan="3" class="text-center">Loading...</td></tr>');

        $.ajax({
            url: '/product/' + id + '/price-history',
            method: 'GET',
            success: function(res) {
                var rows = '';
                if (res.data && res.data.length) {
                    res.data.forEach(function(item) {
                        var change = item.event === 'created' ?
                            'Created → ' + Number(item.new).toLocaleString() :
                            Number(item.old).toLocaleString() + ' → ' + Number(
                                item.new).toLocaleString();
                        rows += '<tr><td>' + item.date + '</td><td>' + item
                            .user + '</td><td>' + change + '</td></tr>';
                    });
                } else {
                    rows =
                        '<tr><td colspan="3" class="text-center">No changes found.</td></tr>';
                }
                modal.find('#priceHistoryBody').html(rows);
            },
            error: function() {
                modal.find('#priceHistoryBody').html(
                    '<tr><td colspan="3" class="text-center text-danger">Error loading data.</td></tr>'
                );
            }
        });
    });
</script>
@endsection
