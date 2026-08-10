@php
    $buyerEntity = $penjualan->buyerEntity();
    $buyerName = $penjualan->customer?->name
        ?: ($penjualan->buyer_name ?: ($buyerEntity?->name ?? '-'));
    $buyerTypeLabel = match ($penjualan->buyer_type) {
        'agent' => 'Agen',
        'canvas' => 'Canvas',
        'outlet' => 'Cabang',
        'toko' => 'Toko',
        default => 'Toko',
    };
    $buyerEntityName = $buyerEntity?->name ?: $penjualan->buyer_display_name;
    $receiptTitle = $penjualan->outlet?->name
        ?: ($penjualan->isWarehouseSale() ? 'GUDANG' : 'PENJUALAN');
    $companyName = $settings['name'] ?? 'NAMA PERUSAHAAN';
    $companyAddress = $settings['address'] ?? '-';
    $saleDate = $penjualan->sale_date ?: $penjualan->created_at ?: now();
    $total = (float) ($penjualan->total ?? $penjualan->items->sum('subtotal'));
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota {{ $penjualan->code }}</title>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            width: 58mm;
            background: #fff;
            color: #000;
            font-family: "Courier New", Courier, monospace;
            font-size: 8px;
            line-height: 1.35;
        }

        .receipt {
            width: 58mm;
            padding: 3mm 2.5mm 4mm;
            background: #fff;
        }

        .receipt-header {
            text-align: center;
            margin-bottom: 1.5mm;
        }

        .logo {
            display: block;
            max-width: 20mm;
            max-height: 20mm;
            margin: 0 auto 1mm;
            object-fit: contain;
            filter: grayscale(100%) contrast(1.2);
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .company-address {
            margin-top: .5mm;
            font-size: 7.5px;
            line-height: 1.4;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .receipt-title {
            margin-top: 1.5mm;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .rule {
            margin: 1.5mm 0;
            border-top: 1px dashed #000;
        }

        .meta {
            margin-bottom: 1mm;
            font-size: 7.5px;
        }

        .meta-row {
            display: flex;
            margin-bottom: .5mm;
        }

        .meta-label {
            flex-shrink: 0;
            width: 12mm;
        }

        .meta-value {
            flex: 1;
            text-align: left;
        }

        .items {
            margin: 1mm 0;
        }

        .item {
            margin-bottom: 1.3mm;
        }

        .item-name {
            font-size: 8px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .item-calc {
            display: flex;
            justify-content: space-between;
            font-size: 7.5px;
            margin-top: .3mm;
        }

        .item-subtotal {
            font-weight: bold;
        }

        .divider-solid {
            margin: 1.5mm 0;
            border-top: 1px solid #000;
        }

        .total-section {
            margin-top: 1mm;
        }

        .total {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            font-weight: bold;
            padding: .8mm 0;
        }

        .total-value {
            white-space: nowrap;
        }

        .thanks {
            text-align: center;
            font-weight: 700;
        }

        .footer {
            margin-top: 3mm;
            font-size: 7.5px;
            text-align: center;
        }

        .print-actions {
            margin: 0 auto 10mm;
            text-align: center;
            font-family: Arial, sans-serif;
        }

        .print-actions button,
        .print-actions a {
            display: inline-block;
            margin: 0 2px;
            padding: 7px 12px;
            border: 1px solid #777;
            border-radius: 3px;
            background: #fff;
            color: #222;
            cursor: pointer;
            text-decoration: none;
        }

        @media screen {
            body {
                width: auto;
                padding: 20px 0;
                background: #e5e5e5;
            }

            .receipt {
                margin: 0 auto;
                box-shadow: 0 0 8px rgba(0, 0, 0, .3);
            }
        }

        @media print {
            html,
            body {
                width: 58mm;
                background: #fff;
            }

            .receipt {
                width: 58mm;
                margin: 0;
            }

            .print-actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <main class="receipt">
        <header class="receipt-header">
            <img class="logo" src="{{ $logoUrl }}" alt="Logo">
            <div class="company-name">{{ $companyName }}</div>
            <div class="company-address">{!! nl2br(e($companyAddress)) !!}</div>
            <div class="receipt-title">{{ $receiptTitle }}</div>
        </header>

        <div class="rule"></div>

        <div class="meta" aria-label="Informasi penjualan">
            <div class="meta-row">
                <span class="meta-label">Tuan</span>
                <span class="meta-value">: {{ $buyerName }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">{{ $buyerTypeLabel }}</span>
                <span class="meta-value">: {{ $buyerEntityName }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">Tgl</span>
                <span class="meta-value">: {{ $saleDate->format('d/m/Y') }}</span>
            </div>
        </div>

        <div class="rule"></div>

        <section class="items" aria-label="Daftar barang">
            @foreach ($penjualan->items as $item)
                @php
                    $qty = (float) ($item->qty_input ?? $item->qty ?? 0);
                    $qtyText = rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',');
                    $priceText = number_format((float) ($item->price ?? 0), 0, ',', '.');
                    $lineSubtotal = (float) ($item->subtotal ?? ($qty * (float) ($item->price ?? 0)));
                @endphp
                <div class="item">
                    <div class="item-name">{{ $item->product?->name ?? '-' }}</div>
                    <div class="item-calc">
                        <span>{{ $qtyText }} x {{ $priceText }}</span>
                        <span class="item-subtotal">{{ number_format($lineSubtotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            @endforeach
        </section>

        <div class="divider-solid"></div>

        <div class="total-section">
            <div class="total">
                <span>TOTAL</span>
                <span class="total-value">Rp {{ number_format($total, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="rule"></div>

        <footer class="footer">
            <div class="thanks">Terima Kasih</div>
        </footer>
    </main>

    <div class="print-actions">
        <button type="button" onclick="window.print()">Print</button>
        <a href="javascript:window.close()">Tutup</a>
    </div>
</body>
</html>
