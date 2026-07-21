# TUTOR.md

## Login Demo

Jika ingin mulai dari data demo:

```bash
php artisan migrate:fresh --seed
```

Login default:

- Email: `superadmin@mailinator.com`
- Password: `password`

## Yang Dipakai Sekarang

### 1. Master dasar

- Supplier: `/supplier`
- Produk: `/product`
- Cabang: `/outlet`
- Agen: `/agents`
- Canvas: `/canvases`
- Salesman: `/salesman`
- User: `/admin`

Catatan:

- Untuk cabang, tetap pakai menu `Outlet`, lalu isi `Jenis Outlet = branch`
- Role yang dipakai sekarang: `superadmin`, `admin-gudang`, `owner`, `staff-outlet`, `sales`

### 2. Pembelian gudang

1. Buat PO di `/pembelian`
2. Terima barang di `/penerimaan`
3. Cek stok di `/stock`
4. Cek kartu stok di `/stock-kartu`
5. Cek stock opname di `/stock-opname`

Catatan qty:

- Stok database tetap disimpan di satuan dasar
- Untuk rokok, contoh umum: `1 Ball = 25 Slop` dan `1 Slop = 10 Pack`
- Form bisa tampil per satuan besar, tetapi data stok tetap basis `Pack`
- `Customer PO` sudah ada di form PO

Catatan template PO:

- Halaman setting ada di `/setting`
- Template default saat ini: `contoh-po-docs.docx` dan `contoh-po-excel.xlsx`
- Di setting bisa upload template PO `DOCX` dan `XLSX`, lalu download template aktif
- Output final PO saat ini masih belum sepenuhnya mengikuti template upload custom

Catatan nomor PO:

- Format nomor PO sekarang bisa diatur per supplier di `/supplier`
- Field supplier yang dipakai: `Prefix Nomor PO` dan `Digit Nomor Urut`

### 3. Penjualan langsung

1. Buka `/penjualan`
2. Pilih buyer: `Agen`, `Canvas`, atau `Cabang`
3. Isi item dan qty
4. Qty boleh input `Pack/Slop/Ball`, tetapi database tetap simpan satuan dasar
5. Simpan transaksi
6. Cetak invoice atau surat jalan dari detail transaksi

Catatan:

- Stok gudang langsung berkurang saat penjualan disimpan
- Jika buyer = `Cabang`, stok cabang langsung tercatat ke `owner_stocks`
- `Cash` dan `Termin` sudah ada di form
- Harga produk otomatis mencoba ambil histori harga terakhir buyer
- Untuk `Cash`, pembayaran langsung tercatat penuh
- Untuk `Termin`, pembayaran bisa dicicil dari halaman `Pembayaran` di list/detail penjualan
- Histori pembayaran partial sudah tersimpan

### 4. Flow cabang lama yang masih aktif

1. Route lama masih ada di `/request-orders`, `/picking-lists`, dan `/delivery-orders`
2. Flow ini sudah tidak ditampilkan di menu utama
3. Dipertahankan hanya sebagai flow legacy / data lama bila sewaktu-waktu masih perlu akses manual

Catatan:

- Flow utama sekarang diarahkan ke `Penjualan`

### 5. Retur yang aktif

#### Gudang ke supplier

- Buka `/refundPembelian?type=gudang_ke_supplier`
- Centang barang yang diretur
- Mode `replacement` langsung selesai sebagai history
- Stok akhir tidak berkurang untuk `replacement`
- Halaman create sudah khusus untuk retur gudang, tanpa tab campur retur cabang

#### Cabang ke gudang

- Buka `/refundPembelian?type=outlet_ke_gudang`
- Untuk `staff-outlet`, halaman langsung terkunci ke retur outlet
- Stok cabang berkurang, stok gudang bertambah
- Halaman create sudah khusus untuk retur cabang, tanpa tab campur retur gudang

Catatan retur:

- Daftar retur admin sudah bisa difilter per type
- Data produk retur sudah dikelompokkan per produk

## Yang Belum Aktif

- Template PO dinamis per supplier untuk output final

## Catatan Flow

- Route `/branchs` adalah legacy
- Menu `Penjualan` sekarang dipakai untuk distribusi langsung `Agen`, `Canvas`, dan `Cabang`
- `Request Cabang`, `Picking & Packing`, dan `Pengiriman Cabang` adalah flow legacy yang disembunyikan dari menu utama

## Data Demo Seed

- `Pabrik ALAMI`
- 2 cabang ALAMI
- 4 agen
- 2 canvas
- 6 salesman
- Stok awal gudang untuk produk rokok ALAMI
