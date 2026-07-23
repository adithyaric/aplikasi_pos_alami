<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $penjualan->code }}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 24px;
            color: #111827;
        }

        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        .muted {
            color: #6b7280;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px 10px;
            font-size: 12px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .summary {
            margin-top: 20px;
            width: 320px;
            margin-left: auto;
        }

        .summary td {
            border: none;
            padding: 4px 0;
        }

        .actions {
            margin-top: 20px;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>

<body>
    <div class="header">
        <div>
            <p class="title">INVOICE PENJUALAN</p>
            <div>ALAMI</div>
            <div class="muted">Gudang Utama</div>
        </div>
        <div>
            <table>
                <tr>
                    <th>No. Invoice</th>
                    <td>{{ $penjualan->code }}</td>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <td>{{ optional($penjualan->sale_date ?? $penjualan->created_at)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th>Pembayaran</th>
                    <td>{{ strtoupper($penjualan->payment_type ?? '-') }} / {{ strtoupper($penjualan->payment_status ?? '-') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table style="margin-bottom:20px">
        <tr>
            <th style="width:180px">Jenis Pembeli</th>
            <td>{{ $penjualan->buyer_type_label }}</td>
        </tr>
        <tr>
            <th>Pembeli</th>
            <td>{{ $penjualan->buyer_display_name }}</td>
        </tr>
        <tr>
            <th>Alamat</th>
            <td>{{ $penjualan->buyer_address ?: '-' }}</td>
        </tr>
        <tr>
            <th>No. Telp</th>
            <td>{{ $penjualan->buyer_phone ?: '-' }}</td>
        </tr>
        <tr>
            <th>Operator</th>
            <td>{{ $penjualan->operator?->name ?? '-' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Produk</th>
                <th style="width:130px">Qty Input</th>
                <th style="width:150px">Qty Database</th>
                <th style="width:130px">Harga</th>
                <th style="width:140px">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $subtotal = 0; @endphp
            @foreach ($penjualan->items as $item)
                @php $subtotal += (int) $item->subtotal; @endphp
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->name ?? '-' }}</td>
                    <td>
                        {{ rtrim(rtrim(number_format((float) ($item->qty_input ?? $item->qty), 2, ',', '.'), '0'), ',') }}
                        {{ $item->unit ?? $item->product?->satuan ?? '' }}
                    </td>
                    <td>{{ $item->product?->qtyDisplay((int) $item->qty) ?? $item->qty }}</td>
                    <td>@currency($item->price) / {{ $item->product?->satuan ?? 'unit' }}</td>
                    <td>@currency($item->subtotal)</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td>Subtotal</td>
            <td style="text-align:right">@currency($subtotal)</td>
        </tr>
        <tr>
            <td>Diskon</td>
            <td style="text-align:right">@currency($penjualan->discount)</td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td style="text-align:right"><strong>@currency($penjualan->total)</strong></td>
        </tr>
    </table>

    <div class="actions">
        <a href="{{ route('penjualan.show', $penjualan) }}">Kembali</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
</body>

</html>
