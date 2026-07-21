# PLAN.md - Posisi Project Saat Ini

## Yang Sudah Aktif
- [x] Master `Cabang`, `Agen`, `Canvas`, dan `Salesman` sudah dipakai
- [x] Menu `Data Cabang` sudah berisi `Cabang` dan `Salesman`
- [x] Role `sales` sudah tersedia di manajemen user
- [x] `Customer PO` sudah ada di form dan list `PO`
- [x] Sistem stok tetap simpan satuan dasar kecil, walau input transaksi bisa pakai satuan besar
- [x] `Kartu Stok`, `Stock Opname`, dan export template stock opname sudah ada
- [x] Retur supplier mode `replacement` hanya jadi history dan tidak mengurangi stok akhir
- [x] Halaman retur admin sudah dipisah per konteks `Gudang -> Supplier` dan `Cabang -> Gudang`
- [x] Daftar retur create sudah tampil per produk, bukan mewajibkan pilih per `SKU/BATCH`
- [x] `Penjualan` langsung sudah aktif untuk `Agen`, `Canvas`, dan `Cabang`
- [x] `Penjualan` langsung memotong stok gudang tanpa approval tambahan
- [x] `Penjualan` ke `Cabang` tetap membentuk `owner_stocks`
- [x] Invoice dan surat jalan `Penjualan` sudah bisa dicetak
- [x] Halaman `Setting` sudah punya upload/download template PO `DOCX` dan `XLSX`
- [x] Format nomor `PO` per supplier sudah aktif
- [x] Setting format nomor `PO` per supplier sudah ada di master supplier
- [x] Harga default `Penjualan` sudah ambil histori harga terakhir per buyer
- [x] Halaman pembayaran/piutang `Penjualan` dengan histori pembayaran partial sudah ada
- [x] Label UI `Outlet` yang bermakna `Cabang` sudah dirapikan di area utama
- [x] Menu flow lama `Request -> Picking -> Delivery` sudah disembunyikan dari menu utama
- [x] Regression check ulang sudah lolos (`php artisan test`)

## Fokus Berikutnya
- [ ] Template cetak PO benar-benar memakai template upload untuk output `DOCX`, `XLSX`, dan `PDF`
- [ ] Output final PO masih perlu dirapikan supaya benar-benar mengikuti template upload

## Checklist Verifikasi
- [x] Flow cabang lama masih jalan: `RequestOrder -> PickingList -> DeliveryOrder -> OwnerStock`
- [x] `Penjualan` buyer `Agen/Canvas/Cabang` sudah bisa disimpan
- [x] Qty input satuan besar tetap tersimpan sebagai satuan dasar kecil
- [x] `Penjualan` bisa cetak invoice
- [x] `Penjualan` bisa cetak surat jalan
- [x] Retur `replacement` tidak mengurangi stok akhir
- [x] Halaman setting template PO sudah tersedia
- [x] Harga terakhir buyer otomatis terisi
- [x] Pembayaran partial `Penjualan` punya halaman kelola tersendiri
