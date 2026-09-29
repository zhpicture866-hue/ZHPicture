@php
    $termins = $project->buildTermins->sortBy('termin_no')->values();
@endphp

<div class="mb-4">

    <div class="row">

        <div class="col-md-6">

            <label class="form-label text-muted">
                Total Penawaran Harga
            </label>

            <div class="fw-semibold">
                Rp {{ number_format($project->rab->grand_total, 0, ',', '.') }}
            </div>

        </div>

        <div class="col-md-6 text-md-end">

            <label class="form-label text-muted">
                Total Termin
            </label>

            <div class="fw-semibold">
                {{ $termins->count() }} Termin
            </div>

        </div>

    </div>

</div>

<div class="table-responsive">

    <table class="table table-bordered align-middle mb-0">

        <thead>

            <tr>

                <th width="80" class="text-center">
                    Termin
                </th>

                <th width="80" class="text-center">
                    %
                </th>

                <th width="150" class="text-center">
                    Nominal
                </th>

                <th>
                    Keterangan
                </th>

                <th>
                    Tanggal Penagihan
                </th>

                <th class="text-center">
                    Invoice
                </th>
                <th class="text-center">
                    Bukti Pembayaran
                </th>
            </tr>

        </thead>

        <tbody>

            @foreach($termins as $index => $termin)

                <tr>

                    <td class="text-center">
                        {{ $termin->termin_no }}
                    </td>

                    <td class="text-center">
                        {{ rtrim(rtrim(number_format($termin->percentage, 2, ',', '.'), '0'), ',') }}%
                    </td>

                    <td class="text-center">
                        Rp {{ number_format($termin->amount, 0, ',', '.') }}
                    </td>

                    <td>
                        {{ $termin->description ?: '-' }}
                    </td>

                    <td>
                        {{ $termin->billing_date->translatedFormat('d F Y') }}
                    </td>

                    <td class="text-center">
                        @include('projects.components.termin-invoice-actions', [
                            'project' => $project,
                            'termins' => $termins,
                            'termin'  => $termin,
                            'index'   => $index,
                        ])
                    </td>
                    <td class="text-center">
                        @include('projects.components.termin-bukti-pembayaran', [
                            'project' => $project,
                            'termin'  => $termin,
                        ])
                    </td>

                </tr>

            @endforeach

        </tbody>

        <tfoot>

            <tr>

                <th colspan="1">
                    Total
                </th>

                <th class="text-end">

                    {{
                        rtrim(
                            rtrim(
                                number_format(
                                    $termins->sum('percentage'),
                                    2,
                                    ',',
                                    '.'
                                ),
                                '0'
                            ),
                            ','
                        )
                    }}%

                </th>

                <th class="text-end">

                    Rp {{ number_format(
                        $termins->sum('amount'),
                        0,
                        ',',
                        '.'
                    ) }}

                </th>

                <th colspan="3"></th>

            </tr>

        </tfoot>

    </table>

</div>