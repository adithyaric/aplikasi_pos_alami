@extends('layouts.master')

@section('title', 'Jurnal Umum')

@section('container')
<section class="content-header"><h1>Jurnal Umum <small>Aktivitas akun dan sub akun transaksi</small></h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <a href="{{ route('accounting.index') }}" class="btn btn-default">Kembali ke Akuntansi</a>
            <a href="{{ route('accounting.journals.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Input Jurnal Manual</a>
            <a href="{{ route('accounting.general-journal.export', request()->query()) }}" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Export XLSX</a>
            <a href="{{ route('accounting.general-journal') }}" class="btn btn-default">Laporan Jurnal</a>
        </div>
        <div class="box-body"><form class="form-inline"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"> s/d <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"> <input name="search" class="form-control" placeholder="No jurnal/keterangan" value="{{ request('search') }}"> <button class="btn btn-default">Filter</button></form></div>
        <div class="table-responsive"><table class="table table-bordered table-hover">
            <thead><tr><th>Tanggal</th><th>No. Jurnal</th><th>Referensi</th><th>Keterangan</th><th class="text-right">Debit</th><th class="text-right">Kredit</th><th>Aksi</th></tr></thead>
            <tbody>@forelse($journals as $journal)
                <tr><td>{{ $journal->transaction_date?->format('d/m/Y') }}</td><td><a href="{{ route('accounting.journals.show', $journal) }}">{{ $journal->journal_number }}</a></td><td>{{ $journal->ref_type ?: 'MANUAL' }}</td><td>{{ $journal->description }}</td><td class="text-right">{{ number_format($journal->details->sum('debit'), 2, ',', '.') }}</td><td class="text-right">{{ number_format($journal->details->sum('credit'), 2, ',', '.') }}</td><td>@if($journal->is_manual)<a class="btn btn-xs btn-warning" href="{{ route('accounting.journals.edit', $journal) }}">Edit</a>@else<span class="label label-info">Otomatis</span>@endif</td></tr>
            @empty <tr><td colspan="7" class="text-center">Belum ada jurnal.</td></tr>@endforelse</tbody>
        </table></div>
        <div class="box-footer">{{ $journals->links() }}</div>
    </div>
</section>
@endsection
