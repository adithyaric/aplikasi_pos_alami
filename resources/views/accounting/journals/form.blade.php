@extends('layouts.master')

@section('title', $journal ? 'Edit Jurnal Umum' : 'Input Jurnal Umum')

@section('container')
@php
    $details = old('details', $journal?->details?->map(fn($detail) => [
        'account_id' => $detail->account_id,
        'debit' => $detail->debit,
        'credit' => $detail->credit,
    ])->all() ?? [[], []]);
@endphp
<section class="content-header"><h1>{{ $journal ? 'Edit' : 'Input' }} Jurnal Umum</h1></section>
<section class="content"><div class="box box-primary">
    <form method="POST" action="{{ $journal ? route('accounting.journals.update', $journal) : route('accounting.journals.store') }}">
        @csrf @if($journal) @method('PUT') @endif
        <div class="box-body">
            <div class="row"><div class="col-md-3"><label>Tanggal</label><input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', $journal?->transaction_date?->format('Y-m-d') ?? now()->toDateString()) }}" required></div><div class="col-md-3"><label>Lokasi</label><select name="outlet_id" class="form-control select2" style="width:100%"><option value="">Pusat</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string) old('outlet_id', $journal?->outlet_id) === (string) $branch->id)>{{ $branch->name }}</option>@endforeach</select></div><div class="col-md-6"><label>Keterangan</label><input name="description" class="form-control" value="{{ old('description', $journal?->description) }}" required></div></div>
            <hr><p class="text-muted">Isi minimal dua baris. Jurnal harus balance.</p>
            <table class="table table-bordered" id="journal-lines"><thead><tr><th width="45%">Akun</th><th width="22%">Debit</th><th width="22%">Kredit</th><th width="11%"></th></tr></thead><tbody>
                @foreach($details as $index => $detail)<tr><td><select name="details[{{ $index }}][account_id]" class="form-control account-select" data-placeholder="Pilih akun"><option value=""></option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected((int)($detail['account_id'] ?? 0) === $account->id)>{{ $account->code }} - {{ $account->name }}</option>@endforeach</select></td><td><input name="details[{{ $index }}][debit]" class="form-control debit" value="{{ $detail['debit'] ?? '' }}" type="number" min="0" step="0.01"></td><td><input name="details[{{ $index }}][credit]" class="form-control credit" value="{{ $detail['credit'] ?? '' }}" type="number" min="0" step="0.01"></td><td><button type="button" class="btn btn-xs btn-danger remove-line"><i class="fa fa-trash"></i></button></td></tr>@endforeach
            </tbody><tfoot><tr><th class="text-right">Total</th><th id="total-debit">0</th><th id="total-credit">0</th><th><button type="button" id="add-line" class="btn btn-xs btn-default"><i class="fa fa-plus"></i></button></th></tr></tfoot></table>
        </div>
        <div class="box-footer"><a href="{{ route('accounting.index') }}" class="btn btn-default">Kembali ke Akuntansi</a> <a href="{{ route('accounting.journals.index') }}" class="btn btn-default">Jurnal Umum</a> <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan Jurnal</button></div>
    </form>
</div></section>
@endsection

@section('page-script')
<script>
$(function(){
    function initAccounts(){ $('.account-select').each(function(){ if(!$(this).hasClass('select2-hidden-accessible')) $(this).select2({placeholder: $(this).data('placeholder'), allowClear: true, width: '100%'}); }); }
    function refresh(){ var debit=0,credit=0; $('.debit').each(function(){debit+=parseFloat($(this).val())||0}); $('.credit').each(function(){credit+=parseFloat($(this).val())||0}); $('#total-debit').text(debit.toLocaleString('id-ID',{minimumFractionDigits:2})); $('#total-credit').text(credit.toLocaleString('id-ID',{minimumFractionDigits:2})); }
    function renumber(){ $('#journal-lines tbody tr').each(function(i){ $(this).find('select,input').each(function(){ $(this).attr('name', $(this).attr('name').replace(/details\[\d+\]/,'details['+i+']')); }); }); }
    $('#add-line').click(function(){ var row=$('#journal-lines tbody tr:first').clone(); row.find('select').val('').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').next('.select2').remove(); row.find('input').val(''); $('#journal-lines tbody').append(row); renumber(); initAccounts(); });
    $(document).on('click','.remove-line',function(){ if($('#journal-lines tbody tr').length>2){$(this).closest('tr').remove();renumber();refresh();} });
    $(document).on('input','.debit,.credit',refresh); initAccounts(); refresh();
});
</script>
@endsection
