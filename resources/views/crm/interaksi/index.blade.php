@extends('tablar::page')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Interaksi Sosial Media</h2>
                <div class="text-secondary">Chat/komentar internal belum dibalas: {{ $belumDibalas }}</div>
            </div>
            <div class="col-12 col-md-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('crm.reaktivasi.index') }}" class="btn btn-outline-dark">Follow-up Hari Ini</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @can('kelola data crm')
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Catat Interaksi</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('crm.interaksi.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label required">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', today()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Platform</label>
                        <select name="platform" class="form-select">
                            @foreach ($platform as $k => $l)
                                <option value="{{ $k }}" @selected(old('platform') === $k)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Jenis</label>
                        <select name="jenis" class="form-select">
                            <option value="ext" @selected(old('jenis') === 'ext')>Eksternal (komentar di akun lain)</option>
                            <option value="int" @selected(old('jenis') === 'int')>Internal (chat/komentar masuk)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">No. WhatsApp customer</label>
                        <input type="text" name="no_whatsapp" class="form-control" inputmode="numeric" placeholder="Bila sudah jadi lead" value="{{ old('no_whatsapp') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tautan postingan/chat</label>
                        <input type="text" name="tautan" class="form-control" value="{{ old('tautan') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Catatan</label>
                        <input type="text" name="catatan" class="form-control" value="{{ old('catatan') }}">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-dark">Simpan interaksi</button>
                    </div>
                </form>
            </div>
        </div>
        @endcan

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h3 class="card-title">Daftar Interaksi</h3>
                <label class="form-check mb-0">
                    <input type="checkbox" class="form-check-input" id="filterBelum">
                    <span class="form-check-label">Hanya yang belum dibalas</span>
                </label>
            </div>
            <div class="table-responsive">
                <table id="tableInteraksi" class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Tanggal</th>
                            <th>Platform</th>
                            <th>Jenis</th>
                            <th>Customer</th>
                            <th>Catatan</th>
                            <th>Status</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(function () {
        const table = $('#tableInteraksi').DataTable({
            serverSide: true,
            processing: true,
            responsive: false,
            scrollX: true,
            order: [[1, 'desc']],
            ajax: {
                url: '{{ route("crm.interaksi.index") }}',
                data: function (d) { d.belum_dibalas = $('#filterBelum').is(':checked') ? 1 : 0; }
            },
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'tanggal', name: 'tanggal' },
                { data: 'platform', name: 'platform' },
                { data: 'jenis', name: 'jenis' },
                { data: 'customer', name: 'customer', orderable: false, searchable: false },
                { data: 'catatan', name: 'catatan' },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ],
            language: {
                search: "",
                searchPlaceholder: "Cari interaksi...",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada data",
                infoFiltered: "(difilter dari _MAX_ total data)",
                zeroRecords: "Data tidak ditemukan",
                paginate: { first: "Awal", last: "Akhir", next: "›", previous: "‹" }
            },
            initComplete: function () {
                $('.dt-search input').removeClass('form-control-sm').addClass('form-control');
            }
        });

        $('#filterBelum').on('change', function () { table.ajax.reload(); });

        $('#tableInteraksi').on('click', '.btn-dibalas', function () {
            const id = $(this).data('id');

            $.ajax({
                url: `/crm/interaksi/${id}/dibalas`,
                method: 'PATCH',
                data: { _token: '{{ csrf_token() }}' },
                success: function (response) {
                    if (response.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Ditandai sudah dibalas.', timer: 1500, showConfirmButton: false });
                        table.ajax.reload(null, false);
                    } else {
                        Swal.fire('Gagal', response.message || 'Tidak bisa memperbarui data.', 'error');
                    }
                },
                error: function () {
                    Swal.fire('Error', 'Terjadi kesalahan saat memperbarui.', 'error');
                }
            });
        });
    });
</script>
@if (session('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: 'Sukses!',
        text: '{{ session('success') }}',
        timer: 2000,
        showConfirmButton: false
    });
</script>
@endif
@endpush
