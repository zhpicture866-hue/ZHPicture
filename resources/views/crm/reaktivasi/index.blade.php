@extends('tablar::page')

@section('content')
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Follow-up Reaktivasi Hari Ini</h2>
                <div class="text-secondary">Customer yang jadwal follow-up-nya jatuh tempo atau sudah lewat.</div>
            </div>
            <div class="col-12 col-md-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('crm.interaksi.index') }}" class="btn btn-outline-dark">Interaksi</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="table-responsive">
                <table id="tableReaktivasi" class="table card-table table-vcenter text-nowrap">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>ID Customer</th>
                            <th>Interval</th>
                            <th>Jadwal</th>
                            <th width="22%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jadwal as $j)
                            <tr>
                                <td>
                                    <a href="{{ route('crm.customers.show', $j->customer) }}">
                                        {{ $j->customer->user?->fullname ?? $j->customer->nic }}
                                    </a>
                                </td>
                                <td>{{ $j->customer->nic }}</td>
                                <td>{{ $j->interval }}</td>
                                <td data-order="{{ $j->tanggal_jadwal->format('Y-m-d') }}">{{ $j->tanggal_jadwal->format('d/m/Y') }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        @if ($j->customer->whatsapp)
                                            <a href="https://wa.me/{{ $j->customer->whatsapp }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark">WhatsApp</a>
                                        @endif
                                        @can('kelola data crm')
                                        <form method="POST" action="{{ route('crm.reaktivasi.selesai', $j) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-dark">Selesai</button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    $(function () {
        $('#tableReaktivasi').DataTable({
            order: [[3, 'asc']],
            language: {
                search: "",
                searchPlaceholder: "Cari customer...",
                lengthMenu: "Tampilkan _MENU_ data",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                infoEmpty: "Tidak ada follow-up yang jatuh tempo",
                zeroRecords: "Tidak ada follow-up yang jatuh tempo",
                paginate: { first: "Awal", last: "Akhir", next: "›", previous: "‹" }
            },
            initComplete: function () {
                $('.dt-search input').removeClass('form-control-sm').addClass('form-control');
            }
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
