# PLAN.md — Roadmap Distribusi Gudang, Cabang, Agen, dan Canvas

## Ringkasan
- Gunakan `outlets` sebagai entitas utama untuk `Cabang`; tabel `branches` dianggap legacy dan tidak jadi basis fitur baru.
- Stok database selalu disimpan di satuan terkecil. Untuk produk rokok, anggap basisnya `pack`; `slop` dan `ball` hanya untuk input dan display.
- `Agen` dan `Canvas` tetap entitas terpisah, tetapi tidak punya login di fase awal.
- Retur pembelian `ganti barang` diubah menjadi catatan/history saja, tanpa mengubah stok akhir.
- `Kartu Stok`, stok opname, laporan, dan transaksi wajib memakai aturan konversi qty yang sama.

## Perubahan Interface dan Data
- `outlets.jenis_outlet` distandardkan agar `Cabang` hidup di `outlets`, bukan di `branches`.
- `salesmans` ditambah relasi ke cabang: minimal `outlet_id`, opsional `user_id` untuk login.
- `users.role` ditambah `sales`; role `staff-outlet` tetap dipakai untuk akun cabang.
- Tambah `partner_prices` dengan kunci `{product_id, buyer_type, buyer_id}` untuk harga khusus `agent|canvas|branch`.
- Tambah `distribution_orders` dan `distribution_order_items` sebagai dokumen komersial gudang ke `agent|canvas|branch`.
- Tambah `distribution_payments` untuk piutang distribusi dengan pola mirip `pembelian_transactions`.
- Tambah `refund_pembelians.return_mode` minimal `replacement|cash_refund`; fase ini mengutamakan `replacement`.

## TODO Berurutan

### Fase 0 — Rapikan Domain
- [ ] Migrasikan data `Cabang` aktif dari `branches` ke `outlets` dan pakai `jenis_outlet = branch`.
- [ ] Bekukan `branches` sebagai legacy CRUD; sidebar/menu baru mengarah ke `outlets` untuk cabang.
- [ ] Lengkapi master `agents` dan `canvases` dengan field operasional: kode, alamat, telepon, termin, limit piutang, status aktif.
- [ ] Kaitkan `salesmans` ke satu cabang, lalu siapkan akun login `sales` bila diperlukan.

### Fase 1 — Fondasi Satuan dan Stok
- [ ] Tetapkan satu aturan global: semua transaksi simpan qty dalam satuan terkecil.
- [ ] Buat satu helper/service normalisasi qty yang dipakai oleh pembelian, retur, distribusi, penjualan, export, dan print.
- [ ] Tetapkan default input per channel: supplier/gudang pakai `slop` atau `ball`, gudang ke `agent|canvas|branch` default `slop`, branch ke sales/customer default `pack`.
- [ ] Samakan tampilan `Kartu Stok`, stok opname, histori stok, dan laporan agar menampilkan qty dasar plus hasil konversi.

### Fase 2 — Fix Retur Pembelian Ganti Barang
- [ ] Ubah retur pembelian `ganti barang` menjadi workflow `replacement` yang selesai sebagai history, bukan pending stock movement.
- [ ] Saat retur replacement dibuat, simpan header, item, alasan, dan referensi supplier tanpa mengurangi stok akhir.
- [ ] Hapus ketergantungan pada proses "terima retur" untuk skenario replacement.
- [ ] Pastikan laporan retur dan kartu stok tetap menampilkan kejadian retur replacement tanpa mengubah saldo stok.

### Fase 3 — Penjualan Gudang ke Agen dan Canvas
- [ ] Bangun `distribution_orders` untuk transaksi gudang ke `agent` dan `canvas`.
- [ ] Flow minimum: draft -> konfirmasi -> potong stok gudang -> cetak invoice -> cetak surat jalan.
- [ ] Semua item `distribution_order` tetap simpan qty dasar; UI input boleh per `slop`.
- [ ] Tambahkan report daterange per `agent` dan per `canvas`.

### Fase 4 — Cabang di Atas Flow Outlet yang Sudah Ada
- [ ] Pertahankan flow fisik cabang yang sudah ada: `RequestOrder -> PickingList -> DeliveryOrder -> OwnerStock`.
- [ ] Hubungkan transaksi cabang ke dokumen komersial `distribution_orders` dengan `buyer_type = branch`.
- [ ] Setelah DO cabang `delivered`, stok masuk ke `owner_stocks` cabang dan dokumen distribusi siap ditagihkan.
- [ ] Jangan buat flow stok cabang baru yang duplikat dengan `RequestOrder/DeliveryOrder`.

### Fase 5 — Sales Cabang ke Customer
- [ ] Jadikan `salesmans` sebagai anak dari cabang dan wajib punya `outlet_id`.
- [ ] Refactor `Penjualan` agar sales menjual dari `owner_stocks` cabang, bukan dari `stocks` gudang.
- [ ] Qty penjualan sales ke customer default `pack` dan tetap tersimpan sebagai qty dasar.
- [ ] Laporan harus bisa rekap per cabang dan breakdown per sales.

### Fase 6 — Harga Khusus dan Piutang
- [ ] Terapkan `partner_prices` untuk harga khusus per `branch`, dan opsional juga untuk `agent` dan `canvas`.
- [ ] Fallback harga tetap `products.harga_jual` bila tidak ada override.
- [ ] Tambahkan `distribution_payments` dengan field inti: tanggal bayar, metode, referensi, nominal, riwayat pembayaran, bukti bayar, status `unpaid|partial|paid`.
- [ ] Piutang fase ini hanya untuk transaksi distribusi gudang ke `agent|canvas|branch`, bukan retail POS customer.

## Test Plan
- [ ] Input `2 slop` untuk produk dengan `1 slop = 10 pack` harus tersimpan sebagai `20 pack`.
- [ ] Produk dengan `1 ball = 25 slop` dan `1 slop = 10 pack` harus tampil konsisten di stok, kartu stok, export, dan print.
- [ ] Retur pembelian `replacement` tidak boleh mengubah stok akhir, tetapi tetap muncul di history dan laporan.
- [ ] Transaksi `agent` dan `canvas` harus bisa cetak invoice, cetak surat jalan, dan mencatat pembayaran bertahap.
- [ ] DO cabang yang `delivered` harus menambah `owner_stocks` cabang dan menyiapkan tagihan distribusi.
- [ ] Akun `sales` hanya boleh menjual stok milik cabangnya sendiri.
- [ ] Harga khusus cabang harus menang atas `harga_jual` default, tetapi hanya untuk cabang yang terkait.
- [ ] Regression check wajib untuk flow lama: PO, penerimaan, request order, picking, delivery order, stock opname, export, dan laporan stok.

## Asumsi
- Basis qty produk rokok di database adalah `pack`.
- `slop` dan `ball` tidak jadi tabel stok terpisah; hanya aturan konversi/input/display.
- `Agen` dan `Canvas` tidak mendapat login di fase awal.
- `Cabang` memakai `outlets` yang sudah ada, bukan `branches`.
- Scope retur pembelian saat ini fokus ke `ganti barang`; refund uang supplier tidak menjadi blocker fase awal.
