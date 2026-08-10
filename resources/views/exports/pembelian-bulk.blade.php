<table>
    <tbody>
        @foreach ($invoices as $invoice)
            <tr>
                <td></td>
                <td colspan="10"><strong>INVOICE {{ $invoice['sequence'] }}</strong></td>
            </tr>
            <tr>
                <td></td>
                <td colspan="9">{{ $company['name'] }}</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td>NIB</td><td></td><td>:</td><td colspan="2"></td>
                <td>No. Transaksi</td><td>:</td><td colspan="2">{{ $invoice['number'] }}</td><td></td>
            </tr>
            <tr>
                <td></td>
                <td>NPPBKC</td><td></td><td>:</td><td colspan="2"></td>
                <td>Tanggal</td><td>:</td><td colspan="2">{{ $invoice['date'] }}</td><td></td>
            </tr>
            <tr>
                <td></td>
                <td>GOL.</td><td></td><td>:</td><td colspan="2"></td>
                <td>Nama</td><td>:</td><td colspan="2">{{ $invoice['buyer']['name'] }}</td><td></td>
            </tr>
            <tr>
                <td></td>
                <td colspan="5"></td>
                <td>Alamat</td><td>:</td><td colspan="2">{{ $invoice['buyer']['address'] }}</td><td></td>
            </tr>
            <tr><td colspan="11"></td></tr>
            <tr>
                <td></td>
                <td colspan="5"></td>
                <td>Telp</td><td>:</td><td colspan="2">{{ $invoice['buyer']['phone'] }}</td><td></td>
            </tr>
            <tr><td colspan="11"></td></tr>
            <tr>
                <td></td><td>NO.</td><td>KODE BARANG</td><td></td><td>KETERANGAN</td>
                <td>QTY</td><td>Dic (%)</td><td>HARGA</td><td></td><td>JUMLAH</td><td></td>
            </tr>
            @forelse ($invoice['items'] as $item)
                <tr>
                    <td></td><td>{{ $item['no'] }}</td><td>{{ $item['code'] }}</td><td></td>
                    <td>{{ $item['name'] }}</td><td>{{ $item['qty'] }} {{ $item['unit'] }}</td>
                    <td>{{ $item['discount'] }}</td><td>{{ $item['price'] }}</td><td></td>
                    <td>{{ $item['subtotal'] }}</td><td></td>
                </tr>
            @empty
                <tr>
                    <td></td><td></td><td></td><td></td><td></td><td></td>
                    <td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endforelse
            <tr><td colspan="11"></td></tr>
            <tr><td colspan="11"></td></tr>
            <tr>
                <td></td><td colspan="8"></td><td>{{ $invoice['subtotal'] }}</td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="5"></td><td colspan="2">Tunggakan Lama</td><td>:</td>
                <td>{{ $invoice['old_debt'] }}</td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="5"></td><td colspan="2">Ongkos Kirim</td><td>:</td>
                <td>{{ $invoice['shipping_cost'] }}</td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="5"></td><td colspan="2">Pembayaran</td><td>:</td>
                <td>{{ $invoice['payment'] }}</td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="5"></td><td colspan="2">Tunggakan Baru</td><td>:</td>
                <td>{{ $invoice['new_debt'] }}</td><td></td>
            </tr>
            <tr>
                <td></td><td colspan="8">PEMBAYARAN : {{ $company['payment'] }}</td><td></td><td></td>
            </tr>
            <tr><td colspan="11"></td></tr>
            <tr>
                <td></td><td colspan="3">MENGETAHUI</td><td colspan="3">DIKIRIM OLEH</td>
                <td colspan="3">DITERIMA OLEH</td><td></td>
            </tr>
            <tr><td colspan="11"></td></tr>
            <tr><td colspan="11"></td></tr>
            <tr><td colspan="11"></td></tr>
            <tr>
                <td></td><td colspan="3">(Oki Fajar Setyawan)</td><td colspan="3">(Hendy widyaputri)</td>
                <td colspan="3">(…........................)</td><td></td>
            </tr>
            <tr><td colspan="11"></td></tr>
        @endforeach
    </tbody>
</table>
