@extends('layouts.master')

@section('title', 'Customer PO')

@section('container')
<section class="content-header">
    <h1>Master Customer PO</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-xs-12">
            <div class="box">
                <div class="box-header">
                    <a href="{{ route('customer-po.create') }}" class="btn btn-sm bg-green">Tambah</a>
                </div>
                <div class="box-body table-responsive">
                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th width="60">No</th>
                                <th>Nama</th>
                                <th>Nama Perusahaan</th>
                                <th>Alamat</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customerPos as $customerPo)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $customerPo->name }}</td>
                                    <td>{{ $customerPo->company_name ?: '-' }}</td>
                                    <td>{{ $customerPo->address ?: '-' }}</td>
                                    <td>{{ $customerPo->phone ?: '-' }}</td>
                                    <td>{{ $customerPo->email ?: '-' }}</td>
                                    <td>
                                        <a class="btn btn-warning btn-xs" href="{{ route('customer-po.edit', $customerPo) }}">Edit</a>
                                        <form action="{{ route('customer-po.destroy', $customerPo) }}" method="post" style="display:inline">
                                            @method('delete')
                                            @csrf
                                            <button class="btn btn-danger btn-xs" onclick="return confirm('Hapus customer PO ini?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
