<table>
    <tbody>
        @foreach ($invoices as $invoice)
            <tr><td colspan="17"></td></tr>
            <tr>
                <td></td><td colspan="7"></td><td></td>
                <td colspan="7"><strong>PURCHASE ORDER</strong></td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="7"></td><td></td><td colspan="7"></td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="7"></td><td></td><td colspan="7"></td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="7"></td><td></td>
                <td colspan="2">NOMOR PO</td><td></td><td colspan="3">TANGGAL</td><td></td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="7"></td><td></td>
                <td colspan="2">{{ $invoice['number'] }}</td><td></td><td colspan="3">{{ $invoice['date'] }}</td><td></td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="7"></td><td></td><td colspan="7"></td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="7">VENDOR</td><td></td><td colspan="7">CUSTOMER</td><td></td>
            </tr>
            <tr>
                <td></td><td>NAMA</td><td></td><td></td><td colspan="4">{{ $invoice['supplier']['name'] }}</td>
                <td></td><td>NAMA</td><td></td><td></td><td colspan="4">{{ $company['name'] }}</td><td></td>
            </tr>
            <tr>
                <td></td><td>NAMA PERUSAHAAN</td><td></td><td></td><td colspan="4">{{ $company['name'] }}</td>
                <td></td><td></td><td></td><td></td><td colspan="4"></td><td></td>
            </tr>
            <tr>
                <td></td><td>ALAMAT</td><td></td><td></td><td colspan="4">{{ $invoice['supplier']['address'] }}</td>
                <td></td><td>ALAMAT</td><td></td><td></td><td colspan="4">{{ $company['address'] }}</td><td></td>
            </tr>
            <tr>
                <td></td><td>PHONE</td><td></td><td></td><td colspan="4">{{ $invoice['supplier']['phone'] }}</td>
                <td></td><td>PHONE</td><td></td><td></td><td colspan="4">{{ $company['phone'] }}</td><td></td>
            </tr>
            <tr>
                <td></td><td>E-MAIL</td><td></td><td></td><td colspan="4"></td>
                <td></td><td>E-MAIL</td><td></td><td></td><td colspan="4">{{ $company['email'] }}</td><td></td>
            </tr>
            <tr><td colspan="17"></td></tr>
            <tr>
                <td></td><td>NO</td><td colspan="3">NAMA BARANG</td><td colspan="3">QTY</td><td></td>
                <td>NO</td><td colspan="3">NAMA BARANG</td><td colspan="3">QTY</td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="4"></td><td>PACK</td><td>SLOP</td><td>BALL</td><td></td>
                <td></td><td colspan="3"></td><td>PACK</td><td>SLOP</td><td>BALL</td><td></td>
            </tr>
            @forelse ($invoice['items'] as $item)
                <tr>
                    <td></td><td>{{ $item['no'] }}</td><td colspan="3">{{ $item['name'] }}</td>
                    <td>{{ $item['qty'] }}</td><td>{{ $item['qty_besar'] }}</td><td>{{ $item['qty_terbesar'] }}</td><td></td>
                    <td>{{ $item['no'] }}</td><td colspan="3">{{ $item['name'] }}</td>
                    <td>{{ $item['qty'] }}</td><td>{{ $item['qty_besar'] }}</td><td>{{ $item['qty_terbesar'] }}</td><td></td>
                </tr>
            @empty
                <tr>
                    <td></td><td></td><td colspan="3"></td><td></td><td></td><td></td><td></td>
                    <td></td><td colspan="3"></td><td></td><td></td><td></td><td></td>
                </tr>
            @endforelse
            <tr><td colspan="17"></td></tr>
            <tr>
                <td></td><td colspan="3">CUSTOMER</td><td colspan="2"></td><td colspan="2">{{ $company['name'] }}</td><td></td>
                <td colspan="3"></td><td colspan="4">{{ $invoice['supplier']['name'] }}</td><td></td>
            </tr>
            <tr><td colspan="17"></td></tr>
            <tr><td colspan="17"></td></tr>
            <tr>
                <td></td><td colspan="3">{{ $company['phone'] }}</td><td colspan="2"></td><td colspan="2">{{ $company['phone'] }}</td><td></td>
                <td colspan="7"></td><td></td>
            </tr>
            <tr><td colspan="17"></td></tr>
        @endforeach
    </tbody>
</table>
