<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan {{ $penjualan->code }}</title>
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

        .signatures {
            margin-top: 48px;
        }

        .signatures td {
            border: none;
            width: 50%;
            padding-top: 48px;
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
            <p class="title">SURAT JALAN</p>
            <div>ALAMI</div>
            <div>Gudang Utama</div>
        </div>
        <div>
            <table>
                <tr>
                    <th>No. Surat Jalan</th>
                    <td>{{ $penjualan->code }}</td>
                </tr>
                <tr>
                    <th>Tanggal</th>
                    <td>{{ optional($penjualan->sale_date ?? $penjualan->created_at)->format('d M Y') }}</td>
                </tr>
                <tr>
                    <th>Operator</th>
                    <td>{{ $penjualan->operator?->name ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table style="margin-bottom:20px">
        <tr>
            <th style="width:180px">Tujuan</th>
            <td>{{ $penjualan->buyer_type_label }} - {{ $penjualan->buyer_display_name }}</td>
        </tr>
        <tr>
            <th>Alamat</th>
            <td>{{ $penjualan->buyer_address ?: '-' }}</td>
        </tr>
        <tr>
            <th>No. Telp</th>
            <td>{{ $penjualan->buyer_phone ?: '-' }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th style="width:40px">No</th>
                <th>Produk</th>
                <th style="width:180px">Jumlah Kirim</th>
                <th style="width:180px">Konversi Database</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($penjualan->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product?->name ?? '-' }}</td>
                    <td>
                        {{ rtrim(rtrim(number_format((float) ($item->qty_input ?? $item->qty), 2, ',', '.'), '0'), ',') }}
                        {{ $item->unit ?? $item->product?->satuan ?? '' }}
                    </td>
                    <td>{{ $item->product?->qtyDisplay((int) $item->qty) ?? $item->qty }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td style="text-align:center">
                Dibuat Oleh,
                <br><br><br><br>
                ______________________
            </td>
            <td style="text-align:center">
                Diterima Oleh,
                <br><br><br><br>
                ______________________
            </td>
        </tr>
    </table>

    <div class="actions">
        <a href="{{ route('penjualan.show', $penjualan) }}">Kembali</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
</body>

</html>
