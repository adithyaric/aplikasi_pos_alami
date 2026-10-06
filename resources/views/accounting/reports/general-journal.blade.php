@extends('layouts.master')
@section('title', 'Jurnal Umum')
@section('container')
<section class="content-header"><h1>Jurnal Umum <small>Detail debit dan kredit</small></h1></section>
<section class="content"><div class="box box-primary">
    <div class="box-header with-border"><a href="{{ route('accounting.index') }}" class="btn btn-default">Kembali ke Akuntansi</a> <a href="{{ route('accounting.journals.index') }}" class="btn btn-default">Jurnal & Akun</a> <form class="form-inline" style="display:inline-block"><select name="location" class="form-control select2" aria-label="Lokasi jurnal" style="width:200px"><option value="pusat" @selected($location === 'pusat')>Pusat</option><option value="all" @selected($location === 'all')>Semua</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($location === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select> <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"> s/d <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"> <button class="btn btn-default">Filter</button> <a class="btn btn-success" href="{{ route('accounting.general-journal.export', request()->query()) }}"><i class="fa fa-file-excel-o"></i> XLSX</a> <a class="btn btn-primary" href="{{ route('accounting.journals.create') }}">Input Manual</a></form></div>
    <div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>Tanggal</th><th>No. Jurnal</th><th>Lokasi</th><th>Akun</th><th>Keterangan</th><th class="text-right">Debit</th><th class="text-right">Kredit</th></tr></thead><tbody>
        @forelse($journals as $journal)
            @foreach($journal->details as $detail)<tr><td>{{ $journal->transaction_date?->format('d/m/Y') }}</td><td><a href="{{ route('accounting.journals.show', $journal) }}">{{ $journal->journal_number }}</a></td><td>{{ $journal->outlet?->name ?? 'Pusat' }}</td><td>{{ $detail->account?->code }} - {{ $detail->account?->name }}</td><td>{{ $journal->description }}</td><td class="text-right">{{ number_format($detail->debit, 2, ',', '.') }}</td><td class="text-right">{{ number_format($detail->credit, 2, ',', '.') }}</td></tr>@endforeach
        @empty <tr><td colspan="7" class="text-center">Tidak ada jurnal pada periode ini.</td></tr>@endforelse
    </tbody></table></div>
    <div class="box-footer">{{ $journals->links() }}</div>
</div></section>
@endsection
