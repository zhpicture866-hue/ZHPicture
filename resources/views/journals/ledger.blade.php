@extends('tablar::page')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">

            <div class="row g-2 align-items-center">

                {{-- LEFT --}}
                <div class="col">
                    <h2>
                        Buku Besar
                    </h2>

                    <div class="text-muted mt-1">
                        Laporan per akun dengan saldo berjalan
                    </div>
                </div>

                {{-- RIGHT --}}
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">

                        <a href="{{ route('ledger.export', request()->all()) }}"
                           class="btn btn-dark"
                           target="_blank">

                            <i class="ti ti-file-export"></i>
                            Ekspor Excel
                        </a>

                        <a href="{{ route('ledgerpdf', request()->all()) }}"
                           class="btn btn-outline-dark"
                           target="_blank">

                            <i class="ti ti-printer"></i>
                            Cetak
                        </a>

                    </div>
                </div>

            </div>

        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('journals.ledger') }}" class="row g-3 align-items-end">

                        <div class="col-md-3">
                            <label class="form-label">Dari</label>
                            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Sampai</label>
                            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-dark text-white w-100">
                                <i class="ti ti-filter"></i> Filter
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            @foreach($ledger as $accountId => $data)

                <div class="card shadow-sm border-0 mb-4">

                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">
                                {{ $data['account']->account_code }} - {{ $data['account']->account_name }}
                            </div>
                            <div class="text-muted small">Detail transaksi akun</div>
                        </div>

                        <span class="badge bg-dark">
                            {{ count($data['rows']) }} Transaksi
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">
                                <tr>
                                    <th width="12%">Tanggal</th>
                                    <th width="15%">Transaksi</th>
                                    <th>Deskripsi</th>
                                    <th class="text-end" width="15%">Debit</th>
                                    <th class="text-end" width="15%">Kredit</th>
                                    <th class="text-end" width="15%">Saldo</th>
                                </tr>
                            </thead>

                            <tbody>
                                @php
                                    $lastJournal = null;
                                    $lastDate = null;
                                @endphp

                                @foreach($data['rows'] as $row)
                                <tr>

                                    {{-- TANGGAL --}}
                                    <td class="text-muted small">
                                        @if($lastDate !== $row['transaction_date'])
                                            {{ \Carbon\Carbon::parse($row['transaction_date'])->format('d M Y') }}
                                            @php $lastDate = $row['transaction_date']; @endphp
                                        @endif
                                    </td>

                                    <td>
                                        @if($lastJournal !== $row['journal_code'])
                                            <a href="{{ route('journals.show', $row['journal_id']) }}" 
                                            class="fw-semibold text-primary text-decoration-none">
                                                {{ $row['journal_code'] }}
                                            </a>
                                            @php $lastJournal = $row['journal_code']; @endphp
                                        @endif
                                    </td>

                                    <td>{{ $row['description'] ?? '-' }}</td>

                                    <td class="text-end text-success">
                                        @if($row['debit'] > 0)
                                            Rp {{ number_format($row['debit'], 0, ',', '.') }}
                                        @endif
                                    </td>

                                    <td class="text-end text-danger">
                                        @if($row['credit'] > 0)
                                            Rp {{ number_format($row['credit'], 0, ',', '.') }}
                                        @endif
                                    </td>

                                    <td class="text-end fw-bold">
                                        Rp {{ number_format($row['balance'], 0, ',', '.') }}
                                    </td>

                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>

                </div>

            @endforeach

        </div>
    </div>
@endsection