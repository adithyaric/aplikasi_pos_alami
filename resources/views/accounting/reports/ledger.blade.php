@extends('layouts.master')
@section('title', 'Buku Besar')
@section('container')
<section class="content-header"><h1>Buku Besar <small>Aktivitas tiap akun</small></h1></section>
<section class="content"><div class="box box-primary">
    <div class="box-header with-border"><a href="{{ route('accounting.index') }}" class="btn btn-default">Kembali ke Akuntansi</a> <form id="ledger-filter" class="form-inline" style="display:inline-block"><select name="account_id" class="form-control select2" data-placeholder="Pilih Akun" style="width: 320px"><option value=""></option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected($ledger && $ledger['account']->id === $account->id)>{{ $account->code }} - {{ $account->name }}</option>@endforeach</select> <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}"> s/d <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}"> <button class="btn btn-default">Tampilkan</button>@if($ledger)<a class="btn btn-success" href="{{ route('accounting.ledger.export', request()->query()) }}"><i class="fa fa-file-excel-o"></i> XLSX</a>@endif</form></div>
    @if($ledger)<div class="box-body"><h4>{{ $ledger['account']->code }} - {{ $ledger['account']->name }}</h4><p>Saldo awal periode: <strong>{{ number_format($ledger['opening'], 2, ',', '.') }}</strong></p><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Tanggal</th><th>No. Jurnal</th><th>Keterangan</th><th class="text-right">Debit</th><th class="text-right">Kredit</th><th class="text-right">Saldo</th></tr></thead><tbody>@forelse($ledgerPage as $row)<tr><td>{{ $row['date']?->format('d/m/Y') }}</td><td>{{ $row['journal_number'] }}</td><td>{{ $row['description'] }}</td><td class="text-right">{{ number_format($row['debit'], 2, ',', '.') }}</td><td class="text-right">{{ number_format($row['credit'], 2, ',', '.') }}</td><td class="text-right">{{ number_format($row['balance'], 2, ',', '.') }}</td></tr>@empty<tr><td colspan="6" class="text-center">Belum ada aktivitas.</td></tr>@endforelse</tbody></table></div></div><div class="box-footer">{{ $ledgerPage->links() }}</div>@endif
</div></section>
@endsection

@section('page-script')
<script>
$(function () {
    $('#ledger-filter').on('submit', function (event) {
        if (!$(this).find('[name="account_id"]').val()) {
            event.preventDefault();
            alert('Pilih akun buku besar terlebih dahulu.');
        }
    });
});
</script>
@endsection
