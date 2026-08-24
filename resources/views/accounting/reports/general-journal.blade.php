@extends('layouts.master')
@section('title', 'Jurnal Umum')
@section('container')
<section class="content-header"><h1>Jurnal Umum <small>Detail debit dan kredit</small></h1></section>
<section class="content"><div class="box box-primary">
    <div class="box-header with-border"><form class="form-inline"><input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"> s/d <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"> <button class="btn btn-default">Filter</button> <a class="btn btn-success" href="{{ route('accounting.general-journal.export', request()->query()) }}"><i class="fa fa-file-excel-o"></i> XLSX</a> <a class="btn btn-primary" href="{{ route('accounting.journals.create') }}">Input Manual</a></form></div>
    <div class="table-responsive"><table class="table table-bordered table-hover"><thead><tr><th>Tanggal</th><th>No. Jurnal</th><th>Akun</th><th>Keterangan</th><th class="text-right">Debit</th><th class="text-right">Kredit</th></tr></thead><tbody>
        @forelse($journals as $journal)
            @foreach($journal->details as $detail)<tr><td>{{ $journal->transaction_date?->format('d/m/Y') }}</td><td><a href="{{ route('accounting.journals.show', $journal) }}">{{ $journal->journal_number }}</a></td><td>{{ $detail->account?->code }} - {{ $detail->account?->name }}</td><td>{{ $journal->description }}</td><td class="text-right">{{ number_format($detail->debit, 2, ',', '.') }}</td><td class="text-right">{{ number_format($detail->credit, 2, ',', '.') }}</td></tr>@endforeach
        @empty <tr><td colspan="6" class="text-center">Tidak ada jurnal pada periode ini.</td></tr>@endforelse
    </tbody></table></div>
</div></section>
@endsection
