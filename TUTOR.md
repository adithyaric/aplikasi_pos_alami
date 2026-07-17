# TUTOR.md

## Login Awal

Jika ingin mulai dari data demo:

```bash
php artisan migrate:fresh --seed
```

Login default:

- Email: `superadmin@mailinator.com`
- Password: `password`

---

## Tutorial Superadmin

### 1. Buat Supplier

Menu:

- `Pembelian > Supplier`

Atau buka:

- `/supplier`

Langkah:

1. Klik `Tambah`.
2. Isi `Kode`.
3. Isi `Nama Supplier`.
4. Isi `Alamat`.
5. Isi `Nomor Telp`.
6. Klik `Simpan`.

---

### 2. Buat Produk Rokok dan Satuannya

Menu:

- `Produk`

Atau buka:

- `/product`

Langkah:

1. Klik `Tambah`.
2. Isi `Nama`.
3. Isi `Barcode`.
4. Pilih `Category`.
5. Pilih `Supplier`.
6. Isi `Harga Beli`.
7. Isi satuan rokok seperti ini:
   - `Satuan Terbesar = Ball`
   - `Isi Konversi Terbesar = 25`
   - `Satuan Besar = Slop`
   - `Satuan = Pack`
   - `Isi Konversi = 10`
8. Klik `Simpan`.

Arti konversi:

- `1 Ball = 25 Slop`
- `1 Slop = 10 Pack`

Catatan:

- Database menyimpan stok dalam satuan dasar, yaitu `Pack`.

---

### 3. Buat Purchase Order ke Supplier

Menu:

- `Pembelian > PO`

Atau buka:

- `/pembelian`

Langkah:

1. Klik `Tambah`.
2. Isi `Kode PO`.
3. Pilih `Supplier`.
4. Tambahkan produk.
5. Isi `Qty`.
6. Isi `Harga Beli`.
7. Klik `Simpan`.

Catatan penting:

- Di form PO saat ini, qty diinput dalam `Satuan Besar`.
- Untuk rokok, artinya input `Slop`.

Contoh:

- Input `Qty = 2`
- Jika `1 Slop = 10 Pack`
- Maka sistem simpan `20 Pack`

---

### 4. Terima Barang dari PO ke Gudang

Menu:

- `Pembelian > Penerimaan Barang`

Atau buka:

- `/penerimaan`

Langkah:

1. Buka PO yang sudah dibuat.
2. Isi `Tanggal Penerimaan`.
3. Isi `Qty Terima`.
4. Klik `Simpan Penerimaan`.

Catatan:

- Di form penerimaan saat ini, qty juga diinput dalam `Satuan Besar`.
- Setelah penerimaan selesai, stok masuk ke gudang.

---

### 5. Cek Stok Gudang

Menu:

- `Stok > Stok`

Atau buka:

- `/stock`

Fungsi:

- Melihat ringkasan stok produk di gudang.

---

### 6. Cek Kartu Stok

Menu:

- `Stok > Kartu Stok`

Atau buka:

- `/stock-kartu`

Fungsi:

- Melihat histori pergerakan stok.
- Qty dasar tetap tampil dalam `Pack`.
- Konversi `Slop` atau `Ball` ikut ditampilkan sebagai informasi.

---

### 7. Buat Cabang Baru

Menu saat ini:

- buka langsung `/outlet`

Langkah:

1. Klik `Tambah Outlet`.
2. Isi `Nama Outlet`.
3. Isi `Jenis Outlet = branch`.
4. Isi `Alamat`.
5. Isi `Deskripsi`.
6. Klik `Simpan`.

Catatan:

- Untuk flow baru, `Cabang` memakai `Outlet`.
- Menu `Affiliate > Anak Cabang` masih legacy.

Contoh:

- `Cabang ALAMI AREA JOGJA KOTA`
- `Cabang ALAMI AREA KULON PROGO`

---

### 8. Buat Akun Cabang / Leader Area

Menu:

- `Pengguna`

Atau buka:

- `/admin`

Langkah:

1. Klik `Tambah`.
2. Isi data user.
3. Pilih `Role = staff-outlet`.
4. Pilih `Outlet` sesuai cabang.
5. Klik `Simpan`.

Fungsi:

- Akun ini dipakai sebagai akun cabang / leader area.

---

### 9. Buat Master Agen

Menu:

- `Affiliate > Agen`

Atau buka:

- `/agents`

Langkah:

1. Klik `Tambah`.
2. Isi `Nama`.
3. Isi `Deskripsi`.
4. Klik `Simpan`.

Contoh data:

- `Superindo`
- `Alfamart Jogja 1`
- `Indomaret Gejayan`
- `Pamela`

Catatan:

- Backend sudah siap untuk field tambahan seperti `kode`, `alamat`, `telepon`, `termin`, dan `limit piutang`.
- Tetapi form UI saat ini masih sederhana.

---

### 10. Buat Master Canvas

Menu:

- `Affiliate > Canvas`

Atau buka:

- `/canvases`

Langkah:

1. Klik `Tambah`.
2. Isi `Nama`.
3. Isi `Deskripsi`.
4. Klik `Simpan`.

Contoh data:

- `Mobil 1 (Pak Handoyo)`
- `Mobil 2 (Pak Budi)`

Catatan:

- Sama seperti agen, form UI saat ini masih sederhana.

---

### 11. Buat Master Salesman

Menu:

- `Salesman`

Atau buka:

- `/salesman`

Langkah:

1. Klik `Tambah`.
2. Isi `Nama Salesman`.
3. Isi `Alamat`.
4. Isi `Nomor Telp`.
5. Klik `Simpan`.

Catatan:

- Backend sudah siap untuk kaitkan salesman ke cabang.
- Tetapi form UI saat ini belum menampilkan field cabang.

---

### 12. Retur Pembelian ke Supplier

Menu:

- `Retur Barang > Retur Barang Gudang`

Atau buka:

- `/refundPembelian?type=gudang_ke_supplier`

Langkah:

1. Klik `Tambah Retur Pembelian`.
2. Pilih `Supplier`.
3. Pilih produk yang akan diretur.
4. Isi `Qty Retur`.
5. Isi `Alasan`.
6. Klik `Simpan Terpilih`.

Catatan flow baru:

- Retur supplier dengan mode `replacement` sekarang hanya menjadi history.
- Stok akhir tidak berkurang.
- Event retur tetap muncul di histori dan laporan.

Contoh:

- Stok awal `40 Pack`
- Retur replacement `20 Pack`
- Diganti supplier `20 Pack`
- Saldo akhir tetap `40 Pack`

---

## Tutorial Staff Outlet / Cabang

### 1. Login sebagai Akun Cabang

Gunakan akun dengan role:

- `staff-outlet`

---

### 2. Buat Permintaan Barang ke Gudang

Menu:

- buka `/request-orders`

Langkah:

1. Klik `Tambah`.
2. Pilih produk yang dibutuhkan.
3. Isi qty permintaan.
4. Simpan request.

Fungsi:

- Cabang mengirim permintaan barang ke gudang.

---

### 3. Proses Gudang

Dilakukan oleh superadmin / admin gudang:

1. Buka `/picking-lists`.
2. Verifikasi request.
3. Generate picking.
4. Selesaikan picking.
5. Generate delivery order.
6. Kirim barang ke cabang.

---

### 4. Terima Barang Cabang

Menu:

- buka `/delivery-orders`

Langkah:

1. Buka delivery order milik cabang.
2. Lakukan proses receive.

Setelah selesai:

- stok cabang masuk ke `owner_stocks`

Lihat di:

- `/owner-stocks`

---

### 5. Retur dari Outlet ke Gudang

Menu:

- buka `/refundPembelian?type=outlet_ke_gudang`

Langkah:

1. Pilih outlet.
2. Pilih produk dari stok outlet.
3. Isi qty retur.
4. Isi alasan.
5. Simpan.

Fungsi:

- Mengurangi stok outlet.
- Mengembalikan stok ke gudang.

---

## Ringkasan Flow Saat Ini

### Flow yang Sudah Bisa Dipakai

- Supplier
- Produk dengan konversi `Ball -> Slop -> Pack`
- PO pembelian
- Penerimaan barang gudang
- Stok gudang
- Kartu stok
- Outlet/Cabang via `outlets`
- Akun cabang `staff-outlet`
- Request order cabang ke gudang
- Picking list
- Delivery order
- Owner stock cabang
- Retur gudang ke supplier
- Retur outlet ke gudang

### Flow yang Belum Selesai

- Penjualan langsung gudang ke `Agen`
- Penjualan langsung gudang ke `Canvas`
- Invoice dan surat jalan khusus flow distribusi agen/canvas
- Pembayaran piutang distribusi agen/canvas/cabang
- Harga khusus per `branch/agent/canvas`
- Penjualan `Sales -> Customer` dari stok cabang

---

## Data Contoh Flow ALAMI

Struktur contoh yang dipakai:

- Supplier: `Pabrik ALAMI`
- Gudang: `Gudang Utama`
- Produk: rokok ALAMI dengan satuan `Pack`, `Slop`, `Ball`
- Agen:
  - `Superindo`
  - `Alfamart Jogja 1`
  - `Indomaret Gejayan`
  - `Pamela`
- Canvas:
  - `Mobil 1 (Pak Handoyo)`
  - `Mobil 2 (Pak Budi)`
- Cabang:
  - `Cabang ALAMI AREA JOGJA KOTA`
  - `Cabang ALAMI AREA KULON PROGO`

