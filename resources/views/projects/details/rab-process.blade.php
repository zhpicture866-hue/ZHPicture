@php
$rab = $project->rab()->with(['items'])->first();
    $canEdit = auth()->user()->can('lihat daftar proyek');
    $ReadOnly = !$canEdit;

function numberToLetters($num) {
    $letters = '';
    $num = $num + 1;

    while ($num > 0) {
        $rem = ($num - 1) % 26;
        $letters = chr(65 + $rem) . $letters;
        $num = intdiv(($num - 1), 26);
    }

    return $letters;
}
@endphp

@can('lihat data proyek')
@if($rab)
<div class="card shadow-sm border-0 mb-4">

    <div class="card-body">

        <div class="row g-4">
            <div class="col-md-4">
                <label class="fw-semibold">Nomor Penawaran</label>
                <input type="text" class="form-control" readonly
                       value="{{ $rab->offer_number }}">
            </div>
            <div class="col-md-4">
                <label class="fw-semibold">Tanggal Penawaran</label>
                <input type="text" class="form-control" readonly
                       value="{{ $rab->offer_date }}">
            </div>
            <div class="col-md-4">
                <label class="fw-semibold">Nama Customer</label>
                <input type="text" class="form-control" readonly
                       value="{{ $rab->contact_name }}">
            </div>
        </div>

        <h5 class="fw-bold mt-5 mb-3">Rincian Pekerjaan</h5>
            <div class="table-responsive">
                <table class="table table-bordered align-middle" style="width: 100%;">
                    <thead>
                        <tr>
                            <th width="5%" style="text-align: center;">NO</th>
                            <th width="50%">URAIAN PEKERJAAN</th>
                            <th width="10%" style="text-align: center;">QTY</th>
                            <th width="17.5%" style="text-align: right;">HARGA SATUAN</th>
                            <th width="17.5%" style="text-align: right;">JUMLAH HARGA</th>
                        </tr>
                    </thead>

                    <tbody>

                        @php
                            // Kategori tidak lagi dikelompokkan/ditampilkan di sini —
                            // disamakan dengan form create/edit yang juga tidak nampilin kategori.
                            $items = $rab->items->sortBy('order_no')->values();
                            $itemNo = 1;
                            $lastDescription = null;
                        @endphp

                        @foreach($items as $item)

                            @php
                                $description = trim((string) $item->description);
                                $showNumber = false;

                                if ($description === '') {
                                    $showNumber = true;
                                } elseif ($description !== $lastDescription) {
                                    $showNumber = true;
                                }

                                $currentNo = $itemNo;

                                if ($showNumber) {
                                    $itemNo++;
                                }

                                $lastDescription = $description;
                            @endphp

                            <tr>
                                <td align="center">
                                    @if($showNumber)
                                        {{ $currentNo }}
                                    @endif
                                </td>
                                <td>
                                    {!! $item->description !!}
                                </td>
                                <td>
                                    {{ rtrim(rtrim(number_format($item->volume, 5, '.', ''), '0'), '.') }}
                                </td>
                                <td>
                                    Rp {{ number_format($item->price, 2, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format($item->total, 2, ',', '.') }}
                                </td>
                            </tr>

                        @endforeach

                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">SUBTOTAL</th>
                            <th>Rp {{ number_format($rab->subtotal, 3, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">DISCOUNT</th>
                            <th>Rp {{ number_format($rab->discount, 3, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">SUBTOTAL AFTER DISCOUNT</th>
                            <th>Rp {{ number_format($rab->subtotal_after_discount, 3, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">TAX RATE</th>
                            <th>{{ $rab->tax_rate }}%</th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">TOTAL TAX</th>
                            <th>Rp {{ number_format($rab->tax_total, 2, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end">SHIPPING / HANDLING</th>
                            <th>Rp {{ number_format($rab->shipping, 2, ',', '.') }}</th>
                        </tr>

                        <tr>
                            <th colspan="4" class="text-end fw-bold">GRAND TOTAL</th>
                            <th class="fw-bold">
                                Rp {{ number_format($rab->grand_total, 3, ',', '.') }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end fw-bold">DIBULATKAN</th>
                            <th class="fw-bold">
                                Rp {{ number_format(floor($rab->grand_total / 100000) * 100000, 0, ',', '.') }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

        @if($rab->notes)
            <div class="mt-4">
                <h5 class="fw-bold">Keterangan</h5>
                <div class="border p-3">{{ $rab->notes }}</div>
            </div>
        @endif
        <div class="d-flex align-items-center gap-2 mt-4">
            @if($project->rab?->id)
                
            <a href="{{ route('projects.rab.pdf', $project->id) }}"
                class="btn btn-dark"
                target="_blank"
                title="Download PDF">
                    <i class="ti ti-download"></i>Download PDF
            </a>
                
            @endif
        </div>
        @if(!$ReadOnly)
            <div class="card mt-3">
                <div class="card-body text-muted small">
                    <div>Dibuat oleh: {{ $rab->creator?->fullname ?? '-' }}</div>
                    <div>Dibuat pada: {{ $rab->created_at?->format('d M Y H:i') }}</div>
                    <div>Terakhir diubah: {{ $rab->updated_at?->format('d M Y H:i') }}</div>
                    <div>Diubah oleh: {{ $rab->editor?->fullname ?? '-' }}</div>
                </div>
            </div>
        @endif
    </div>
</div>
@endif
@endcan