@extends('layouts.master')

@section('title', 'Penerimaan Barang (Pembelian)')

@section('container')
    <section class="content-header">
        <h1>Penerimaan Barang <small>Pembelian</small></h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-header">
                    </div>
                    <div class="box-body table-responsive text-nowrap">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th>Kode PO</th>
                                    <th>Supplier</th>
                                    <th>Items</th>
                                    <th>Total PO</th>
                                    <th width="130">Status Penerimaan</th>
                                    <th width="130">Status PO</th>
                                    <th>Tgl Terima</th>
                                    <th width="180">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pembelians as $value)
                                    @php
                                        $receiptStatus = $value->receipt_status ?? 'draft';
                                        $receiptBadge = match($receiptStatus) {
                                            'completed' => 'success',
                                            'validated' => 'info',
                                            default     => 'warning',
                                        };
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><strong>{{ $value->code }}</strong></td>
                                        <td>{{ $value->supplier?->name }}</td>
                                        <td>
                                            @php $totalItems = $value->pembelianProducts->count(); @endphp
                                            <ul class="list-unstyled" style="margin:0">
                                                @foreach ($value->pembelianProducts as $index => $item)
                                                    <li class="@if($index >= 3) extra-item-pembelian-{{ $value->id }} @endif"
                                                        @if($index >= 3) style="display:none" @endif>
                                                        <small>{{ $item->product?->name }}</small>

                                                        @if($item->product?->konversi_qty && $item->product?->satuan_besar)
                                                            <span class="label label-info">{{ $item->product?->konversiDisplay($item->qty) }}</span>
                                                        @else
                                                            <span class="label label-default">{{ $item->qty }}</span>
                                                        @endif

                                                        @if($value->stocks->where('product_id', $item->product_id)->count())
                                                            <span class="label label-success">
                                                                ✓
                                                                @if($item->product?->konversi_qty && $item->product?->satuan_besar)
                                                                    {{ $item->product?->konversiDisplay($item->qty_diterima) }}
                                                                @else
                                                                    {{ $item->qty_diterima }}
                                                                @endif
                                                                diterima
                                                            </span>
                                                        @else
                                                            <span class="label label-warning">Belum diterima</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>

                                            @if($totalItems > 3)
                                                <a href="javascript:void(0)"
                                                class="btn-toggle-pembelian-items"
                                                data-target="{{ $value->id }}"
                                                data-state="closed">
                                                    <span class="label label-default">
                                                        Selengkapnya ({{ $totalItems - 3 }})
                                                    </span>
                                                </a>
                                            @endif
                                        </td>
                                        <td>@currency($value->total)</td>
                                        @php
                                            $payStatus = $value->pembelianTransaction?->status ?? 'unpaid';
                                            $payBadge = match ($payStatus) {
                                                'paid' => 'success',
                                                'partial' => 'warning',
                                                default => 'danger',
                                            };
                                        @endphp
                                        <td>
                                            <span class="label label-{{ $receiptBadge }}">
                                                {{ strtoupper($receiptStatus) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="label label-{{ $payBadge }}">
                                                {{ match($payStatus) {
                                                    'paid'    => 'LUNAS',
                                                    'unpaid'  => 'BELUM LUNAS',
                                                    'partial' => 'PARTIAL',
                                                    default   => strtoupper($payStatus)
                                                } }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $value->receipt_date ? \Carbon\Carbon::parse($value->receipt_date)->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td>
                                            <a href="{{ route('pembelian.penerimaan', $value) }}"
                                               class="btn btn-xs btn-{{ $receiptStatus === 'completed' ? 'default' : 'primary' }}">
                                                <i class="fa fa-{{ $receiptStatus === 'completed' ? 'eye' : 'edit' }}"></i>
                                                {{ $receiptStatus === 'completed' ? 'Detail' : 'Input Pembelian' }}
                                            </a>
                                            {{-- @if($value->stocks->count()) --}}
                                                <a href="{{ route('laporan.penerimaan', [$value->id, 'po']) }}"
                                                   class="btn btn-xs btn-success" title="Export Pembelian">
                                                    <i class="fa fa-file-excel-o"></i> Cetak Excel
                                                </a>
                                                <a href="{{ route('laporan.pdf.penerimaan-single', $value->id) }}" target="_blank" class="btn btn-xs btn-danger" title="Export PDF Pembelian">
                                                    <i class="fa fa-file-pdf-o"></i> Cetak Pdf
                                                </a>
                                            {{-- @endif --}}
                                        </td>
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
@section('page-script')
    <script>
        $(document).on('click', '.btn-toggle-pembelian-items', function () {
        var id = $(this).data('target');
        var state = $(this).data('state');
        var $extra = $('.extra-item-pembelian-' + id);
        var $badge = $(this).find('.label');

        if (state === 'closed') {
            $extra.show();
            $badge.text('Tutup');
            $(this).data('state', 'open');
        } else {
            $extra.hide();
            $badge.text('Selengkapnya (' + $extra.length + ')');
            $(this).data('state', 'closed');
        }
    });
    </script>
@endsection
