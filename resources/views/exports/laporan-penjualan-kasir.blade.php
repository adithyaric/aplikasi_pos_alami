<table>
    <thead>
        <tr>
            <th>Code</th>
            <th>Customer</th>
            <th>Kasir</th>
            <th>Discount</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($penjualans as $penjualan)
            @php
                $totalDiscount = (int) ($penjualan->discount ?? 0)
                    + $penjualan->items->sum(fn ($item) => (int) ($item->discount ?? 0));
            @endphp
            <tr>
                <td>{{ $penjualan->code }}</td>
                <td>{{ $penjualan->customer->name }}</td>
                <td>{{ $penjualan->kasir->name ?? ''}}</td>
                <td>@currency($totalDiscount)</td>
                <td>@currency($penjualan->total)</td>
            </tr>
        @endforeach
    </tbody>
</table>
