<div class="mb-4">

    <div class="row">

        <div class="col-md-6">

            <label class="form-label text-muted">
                Total Penawaran Build
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
                {{ $project->buildTermins->count() }} Termin
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

                <th width="150" class="text-end">
                    Persentase
                </th>

                <th width="220" class="text-end">
                    Nominal
                </th>

                <th>
                    Keterangan
                </th>

            </tr>

        </thead>

        <tbody>

            @foreach($project->buildTermins->sortBy('termin_no') as $termin)

                <tr>

                    <td class="text-center">
                        {{ $termin->termin_no }}
                    </td>

                    <td class="text-end">
                        {{ rtrim(rtrim(number_format($termin->percentage, 2, ',', '.'), '0'), ',') }}%
                    </td>

                    <td class="text-end">
                        Rp {{ number_format($termin->amount, 0, ',', '.') }}
                    </td>

                    <td>
                        {{ $termin->description ?: '-' }}
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
                                    $project->buildTermins->sum('percentage'),
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
                        $project->buildTermins->sum('amount'),
                        0,
                        ',',
                        '.'
                    ) }}

                </th>

                <th></th>

            </tr>

        </tfoot>

    </table>

</div>