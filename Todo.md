# Todo.md - Ringkas Revisi

## Sudah Beres
- [x] `Customer PO` sudah ada di pembelian
- [x] Retur supplier `replacement` hanya jadi history dan stok akhir tetap aman
- [x] Retur create admin sudah dipisah per konteks, bukan dua tab dalam satu page
- [x] Data retur create sudah dikelompokkan per produk
- [x] Menu `Data Cabang` sudah berisi `Cabang` dan `Salesman`
- [x] Field `Cabang` untuk `Salesman` sudah tampil di form
- [x] Role `sales` sudah tersedia di user management
- [x] `Penjualan` langsung untuk `Agen`, `Canvas`, dan `Cabang` sudah aktif
- [x] `Penjualan` langsung memotong stok gudang
- [x] `Penjualan` ke cabang tetap menambah `owner_stocks`
- [x] Invoice dan surat jalan `Penjualan` sudah tersedia
- [x] Halaman setting template PO sudah tersedia dengan fallback file contoh repo
- [x] Format nomor PO per supplier
- [x] Setting format nomor PO per supplier di master supplier
- [x] Harga terakhir buyer otomatis jadi default di `Penjualan`
- [x] Halaman pembayaran partial / piutang `Penjualan` sudah ada
- [x] Histori pembayaran `Penjualan` sudah ada
- [x] Sisa label `Outlet` utama sudah dirapikan ke `Cabang`
- [x] Menu flow lama `Request`, `Picking`, `Delivery` sudah di-hide dari menu utama
- [x] Regression check lulus

## Masih Perlu

### PO
- [ ] Template output PO benar-benar mengikuti file template upload
- [ ] Output PO `DOCX`, `XLSX`, dan `PDF` masih perlu dirapikan

### Catatan
- Route dan model lama masih tetap memakai nama internal `outlet`, tapi di UI utama sudah diarahkan sebagai `Cabang`
- Route legacy `Request / Picking / Delivery` masih ada jika diakses manual, hanya tidak ditampilkan di menu utama
