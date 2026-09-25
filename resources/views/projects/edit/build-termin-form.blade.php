<form
    action="{{ route('projects.build-termin.update', $project->id) }}"
    method="POST"
    id="build-termin-edit-form"
>
    @csrf
    @method('PUT')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @php
        $offerTotal = (float) ($project->rab?->grand_total ?? 0);
    @endphp
    <div class="card border-0 bg-light mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small mb-1">
                        TOTAL PENAWARAN BUILD
                    </div>

                    <div class="fs-2 fw-bold text-dark">
                        Rp {{ number_format($offerTotal, 0, ',', '.') }}
                    </div>

                    <div class="text-muted small mt-1">
                        Nilai ini menjadi dasar perhitungan setiap termin.
                    </div>
                </div>

                <div class="avatar avatar-lg bg-white shadow-sm">
                    <i class="ti ti-receipt-2 fs-2"></i>
                </div>
            </div>
        </div>
    </div>

    <input
        type="hidden"
        id="build-edit-offer-total"
        value="{{ $offerTotal }}"
    >
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-1 fw-bold">
                Setting Termin Pembayaran
            </h3>

            <div class="text-muted">
                Atur pembagian pembayaran berdasarkan persentase termin.
            </div>
        </div>

        <button
            type="button"
            id="btn-add-edit-termin"
            class="btn btn-dark"
            title="Tambah Termin"
        >
            <i class="ti ti-plus me-1"></i>
        </button>
    </div>

    <div id="termin-edit-container">

        @foreach($project->buildTermins->sortBy('termin_no') as $termin)

            <div class="card border-0 shadow-sm mb-3 termin-card termin-row">

                <div class="card-body p-3">

                    <div class="row g-3 align-items-end">

                        {{-- NOMOR --}}
                        <div class="col-md-1">

                            <label class="form-label text-muted small">
                                Termin
                            </label>

                            <div class="termin-number">
                                <span class="termin-no"></span>
                            </div>

                        </div>


                        {{-- PERSENTASE --}}
                        <div class="col-md-2">

                            <label class="form-label small fw-semibold">
                                Persentase
                            </label>

                            <div class="input-group">

                                <input
                                    type="number"
                                    name="percentage[]"
                                    class="form-control termin-percentage"
                                    min="0.01"
                                    max="100"
                                    step="0.01"
                                    placeholder="30"
                                    value="{{ $termin->percentage }}"
                                    required
                                >

                                <span class="input-group-text">
                                    %
                                </span>

                            </div>

                        </div>


                        {{-- NOMINAL --}}
                        <div class="col-md-3">

                            <label class="form-label small fw-semibold">
                                Nominal Pembayaran
                            </label>

                            <input
                                type="text"
                                class="form-control termin-amount fw-bold"
                                value="Rp {{ number_format($termin->amount, 0, ',', '.') }}"
                                readonly
                            >

                        </div>


                        {{-- KETERANGAN --}}
                        <div class="col-md-5">

                            <label class="form-label small fw-semibold">
                                Keterangan
                            </label>

                            <input
                                type="text"
                                name="termin_description[]"
                                class="form-control"
                                value="{{ $termin->description }}"
                                placeholder="Contoh: DP / Pembayaran Tahap 1 / Pelunasan"
                            >

                        </div>


                        {{-- DELETE --}}
                        <div class="col-md-1">

                            <button
                                type="button"
                                class="btn btn-dark btn-icon btn-remove-termin"
                                title="Hapus Termin"
                            >
                                <i class="ti ti-trash"></i>
                            </button>

                        </div>

                    </div>

                </div>

            </div>

        @endforeach

    </div>

    <div class="card border-0 shadow-sm mt-4">

        <div class="card-body p-4">

            <div class="row g-4">

                <div class="col-md-6">
                    <div class="summary-item">

                        <div class="summary-icon">
                            <i class="ti ti-percentage"></i>
                        </div>

                        <div>
                            <div class="text-muted small">
                                Total Persentase
                            </div>

                            <div
                                id="total-termin-percentage-edit"
                                class="fs-3 fw-bold"
                            >
                                0%
                            </div>
                        </div>

                    </div>
                </div>

                <div class="col-md-6">
                    <div class="summary-item">

                        <div class="summary-icon">
                            <i class="ti ti-cash"></i>
                        </div>

                        <div>
                            <div class="text-muted small">
                                Total Nominal
                            </div>

                            <div
                                id="total-termin-amount-edit"
                                class="fs-3 fw-bold"
                            >
                                Rp 0
                            </div>
                        </div>

                    </div>
                </div>

            </div>
            <div
                id="termin-warning-edit"
                class="alert alert-warning mt-4 mb-0 d-none"
            >
                <div class="d-flex align-items-center">
                    <i class="ti ti-alert-triangle me-2 fs-2"></i>

                    <div>
                        <div class="fw-bold">
                            Persentase belum lengkap
                        </div>

                        <div class="small">
                            Total persentase termin harus tepat 100%.
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    {{-- <div class="d-flex justify-content-end mt-4 gap-2">

        <button type="submit" class="btn btn-dark" id="btn-update-termin">
            <i class="ti ti-device-floppy"></i>
            Simpan Perubahan
        </button>
        <button type="button" class="btn btn-secondary btn-cancel">
            <i class="ti ti-x"></i> Batal
        </button>
    </div> --}}

</form>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('build-termin-edit-form');

    if (!form) {
        return;
    }

    const container = document.getElementById('termin-edit-container');
    const addButton = document.getElementById('btn-add-edit-termin');
    const offerTotalInput = document.getElementById('build-edit-offer-total');

    const totalPercentageElement = document.getElementById('total-termin-percentage-edit');

    const totalAmountElement = document.getElementById('total-termin-amount-edit');

    const warningElement = document.getElementById('termin-warning-edit');

    const saveButton = document.getElementById('btn-update-termin');

    const offerTotal = parseFloat(
        offerTotalInput?.value || 0
    ) || 0;


    function formatRupiah(value) {

        value = Number(value) || 0;

        return 'Rp ' + new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0
        }).format(value);

    }

    function getRows() {

        return container.querySelectorAll(
            '.termin-row'
        );

    }

    function updateTerminNumbers() {

        const rows = getRows();

        rows.forEach((row, index) => {

            const numberElement =
                row.querySelector('.termin-no');

            if (numberElement) {

                numberElement.textContent =
                    index + 1;

            }

        });

    }

    function calculateTermin() {

        const rows = getRows();

        let totalPercentage = 0;
        let totalAmount = 0;


        rows.forEach(row => {

            const percentageInput = row.querySelector('.termin-percentage');

            const amountInput = row.querySelector('.termin-amount');

            const percentage =
                parseFloat(
                    percentageInput?.value || 0
                ) || 0;


            const amount = offerTotal * (percentage / 100);

            totalPercentage += percentage;
            totalAmount += amount;

            if (amountInput) {

                amountInput.value =
                    formatRupiah(amount);

            }

        });

        if (totalPercentageElement) {

            totalPercentageElement.textContent =
                totalPercentage.toFixed(2) + '%';

        }

        if (totalAmountElement) {

            totalAmountElement.textContent =
                formatRupiah(totalAmount);

        }

        const isValid = Math.abs(totalPercentage - 100) < 0.01;


        if (isValid) {

            if (warningElement) {

                warningElement.classList.add(
                    'd-none'
                );

            }

            if (saveButton) {

                saveButton.disabled = false;

            }

        } else {

            if (warningElement) {

                warningElement.classList.remove(
                    'd-none'
                );

            }

            if (saveButton) {

                saveButton.disabled = true;

            }

        }

    }

    function createTerminRow() {

        const row = document.createElement('div');

        row.className = 'card border-0 shadow-sm mb-3 termin-card termin-row';

        row.innerHTML = `

            <div class="card-body p-3">

                <div class="row g-3 align-items-end">

                    <div class="col-md-1">

                        <label class="form-label text-muted small">
                            Termin
                        </label>

                        <div class="termin-number">

                            <span class="termin-no"></span>

                        </div>

                    </div>

                    <div class="col-md-2">

                        <label class="form-label small fw-semibold">
                            Persentase
                        </label>

                        <div class="input-group">

                            <input
                                type="number"
                                name="percentage[]"
                                class="form-control termin-percentage"
                                min="0.01"
                                max="100"
                                step="0.01"
                                placeholder="30"
                                required
                            >

                            <span class="input-group-text">
                                %
                            </span>

                        </div>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label small fw-semibold">
                            Nominal Pembayaran
                        </label>

                        <input
                            type="text"
                            class="form-control termin-amount fw-bold"
                            value="Rp 0"
                            readonly
                        >

                    </div>

                    <div class="col-md-5">

                        <label class="form-label small fw-semibold">
                            Keterangan
                        </label>

                        <input
                            type="text"
                            name="termin_description[]"
                            class="form-control"
                            placeholder="Contoh: DP / Pembayaran Tahap 1 / Pelunasan"
                        >

                    </div>

                    <div class="col-md-1">

                        <button
                            type="button"
                            class="btn btn-dark btn-icon w-100 btn-remove-termin"
                            title="Hapus Termin"
                        >
                            <i class="ti ti-trash"></i>
                        </button>

                    </div>

                </div>

            </div>

        `;

        return row;
    }

    if (addButton) {

        addButton.addEventListener(
            'click',
            function () {

                const row = createTerminRow();

                container.appendChild(row);

                updateTerminNumbers();

                calculateTermin();

            }
        );

    }

    container.addEventListener(
        'click',
        function (event) {

            const removeButton =
                event.target.closest(
                    '.btn-remove-termin'
                );


            if (!removeButton) {
                return;
            }

            const rows = getRows();

            if (rows.length <= 1) {

                return;

            }

            const row = removeButton.closest('.termin-row');


            if (row) {

                row.remove();

                updateTerminNumbers();

                calculateTermin();

            }

        }
    );

    container.addEventListener(
        'input',
        function (event) {

            if (
                event.target.classList.contains(
                    'termin-percentage'
                )
            ) {

                calculateTermin();

            }

        }
    );

    form.addEventListener(
        'submit',
        function (event) {

            const rows =
                getRows();


            let totalPercentage = 0;


            rows.forEach(row => {

                const input =
                    row.querySelector(
                        '.termin-percentage'
                    );


                totalPercentage +=
                    parseFloat(
                        input?.value || 0
                    ) || 0;

            });

            if (
                Math.abs(
                    totalPercentage - 100
                ) > 0.01
            ) {

                event.preventDefault();

                if (warningElement) {

                    warningElement.classList.remove(
                        'd-none'
                    );

                }

                alert(
                    'Total persentase termin harus tepat 100%.'
                );

                return;

            }

        }
    );

    updateTerminNumbers();

    calculateTermin();

});
</script>
@endpush