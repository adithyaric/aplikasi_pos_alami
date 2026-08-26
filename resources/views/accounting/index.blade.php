@extends('layouts.master')

@section('title', 'Akuntansi')

@section('container')
<section class="content-header">
    <h1>Akuntansi <small>Jurnal dan laporan keuangan</small></h1>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-3 col-sm-6"><div class="small-box bg-aqua"><div class="inner"><h3>{{ $accountCount }}</h3><p>Akun Perkiraan</p></div><div class="icon"><i class="fa fa-list"></i></div><a href="{{ route('accounting.accounts.index') }}" class="small-box-footer">Kelola Akun <i class="fa fa-arrow-circle-right"></i></a></div></div>
        <div class="col-md-3 col-sm-6"><div class="small-box bg-green"><div class="inner"><h3>{{ $journalCount }}</h3><p>Jurnal Tercatat</p></div><div class="icon"><i class="fa fa-book"></i></div><a href="{{ route('accounting.journals.index') }}" class="small-box-footer">Jurnal Umum <i class="fa fa-arrow-circle-right"></i></a></div></div>
        <div class="col-md-3 col-sm-6"><div class="small-box bg-yellow"><div class="inner"><h3>&nbsp;</h3><p>Buku Besar</p></div><div class="icon"><i class="fa fa-file-text"></i></div><a href="{{ route('accounting.ledger') }}" class="small-box-footer">Lihat Laporan <i class="fa fa-arrow-circle-right"></i></a></div></div>
        <div class="col-md-3 col-sm-6"><div class="small-box bg-purple"><div class="inner"><h3>&nbsp;</h3><p>Laba Rugi & Neraca</p></div><div class="icon"><i class="fa fa-bar-chart"></i></div><a href="{{ route('accounting.profit-loss') }}" class="small-box-footer">Lihat Laporan <i class="fa fa-arrow-circle-right"></i></a></div></div>
    </div>

    <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-refresh"></i> Sinkronisasi transaksi lama</h3></div>
        <div class="box-body">
            <p>Gunakan satu kali setelah modul akuntansi dipasang untuk membuat jurnal dari transaksi Penjualan, Pembelian yang sudah diterima, dan Pengeluaran. Transaksi baru akan dijurnal otomatis.</p>
            <form method="POST" action="{{ route('accounting.sync') }}">@csrf<button class="btn btn-primary"><i class="fa fa-refresh"></i> Sinkronkan Sekarang</button></form>
        </div>
    </div>

</section>
@endsection
