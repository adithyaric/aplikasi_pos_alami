<div class="row">
    <div class="col-md-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-book"></i> Akun &amp; Jurnal Umum</h3>
                <div class="box-tools pull-right">
                    <a href="{{ route('accounting.accounts.index') }}" class="btn btn-xs btn-default"><i class="fa fa-list"></i> Kelola Semua Akun</a>
                    <a href="{{ route('accounting.journals.index') }}" class="btn btn-xs btn-default"><i class="fa fa-book"></i> Kelola Jurnal</a>
                    <button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#modal-create-account"><i class="fa fa-plus"></i> Buat COA</button>
                </div>
            </div>
            <div class="box-body">
                <div class="alert alert-info">
                    Jurnal dari penjualan, penerimaan pembelian, pembayaran, dan pengeluaran dibuat otomatis. Jurnal tambahan dapat diinput manual melalui menu Jurnal Umum.
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-6"><a class="btn btn-success btn-block" href="{{ route('accounting.general-journal') }}"><i class="fa fa-book"></i> Jurnal Umum</a></div>
                    <div class="col-md-3 col-sm-6"><a class="btn btn-info btn-block" href="{{ route('accounting.ledger') }}"><i class="fa fa-file-text-o"></i> Buku Besar</a></div>
                    <div class="col-md-3 col-sm-6"><a class="btn btn-warning btn-block" href="{{ route('accounting.profit-loss') }}"><i class="fa fa-line-chart"></i> Laba Rugi</a></div>
                    <div class="col-md-3 col-sm-6"><a class="btn btn-primary btn-block" href="{{ route('accounting.balance-sheet') }}"><i class="fa fa-balance-scale"></i> Neraca</a></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-md-8">
                        <h4>Chart of Accounts</h4>
                        <div class="table-responsive"><table class="table table-condensed table-bordered">
                            <thead><tr><th>Kode</th><th>Nama Akun</th><th>Tipe</th><th>Induk</th><th>Status</th></tr></thead>
                            <tbody>@forelse($accountingAccounts->take(25) as $account)<tr><td>{{ $account->code }}</td><td>{{ $account->name }}</td><td>{{ $account->type_code }}</td><td>{{ $account->parent?->code ?: '-' }}</td><td>{!! $account->is_active ? '<span class="label label-success">Aktif</span>' : '<span class="label label-default">Nonaktif</span>' !!}</td></tr>@empty<tr><td colspan="5" class="text-center">Belum ada akun.</td></tr>@endforelse</tbody>
                        </table></div>
                        @if($accountingAccounts->count() > 25)<a href="{{ route('accounting.accounts.index') }}">Lihat semua akun...</a>@endif
                    </div>
                    <div class="col-md-4">
                        <h4>Sinkronisasi Transaksi</h4>
                        <p class="text-muted">Membuat ulang jurnal otomatis dari seluruh transaksi lama secara aman tanpa duplikasi.</p>
                        <form method="POST" action="{{ route('accounting.sync') }}">@csrf<button class="btn btn-default"><i class="fa fa-refresh"></i> Sinkronkan Jurnal</button></form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modal-create-account" tabindex="-1" role="dialog" aria-labelledby="modal-create-account-label">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <form method="POST" action="{{ route('accounting.accounts.store') }}">
            @csrf
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title" id="modal-create-account-label"><i class="fa fa-plus"></i> Buat Akun / Sub Akun</h4></div>
            <div class="modal-body">
                <div class="form-group"><label>Kode Perkiraan</label><input name="code" class="form-control" maxlength="20" required></div>
                <div class="form-group"><label>Nama Akun</label><input name="name" class="form-control" maxlength="100" required></div>
                <div class="form-group"><label>Tipe Akun</label><input name="type_code" class="form-control" placeholder="BANK, AREC, REVE, EXPS, ..." required></div>
                <div class="form-group"><label>Akun Induk</label><select name="parent_id" class="form-control"><option value="">Tanpa induk</option>@foreach($accountingParents as $parent)<option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>@endforeach</select></div>
                <label class="checkbox-inline"><input type="checkbox" name="is_header" value="1"> Akun header (tidak dapat diposting)</label>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Batal</button><button class="btn btn-primary"><i class="fa fa-save"></i> Simpan Akun</button></div>
        </form>
    </div></div>
</div>
