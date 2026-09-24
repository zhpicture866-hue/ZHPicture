<form id="rab-edit-form" action="{{ route('projects.rab.update', [$project->id, $rab->id]) }}" method="POST">
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

    <input type="hidden" name="project_id" value="{{ $project->id }}">

    <h4 class="fw-bold mb-3">Informasi Pembuatan Rab</h4>

    <div class="row g-3">
        <div class="col-md-4">
            <label>Nomor Penawaran</label>
            <input type="text" name="offer_number" class="form-control" value="{{ old('offer_number', $rab->offer_number) ?? '' }}" placeholder="Auto Generate" readonly>

        </div>
        <div class="col-md-4">

            <label class="form-label fw-semibold">
                Tanggal Penawaran
            </label>

            <div class="input-icon">
                <span class="input-icon-addon">
                    <i class="ti ti-calendar"></i>
                </span>

                <input type="text"
                    name="offer_date"
                    id="offer_date"
                    class="form-control"
                    placeholder="dd/mm/yyyy"
                    value="{{ old('offer_date', $rab->offer_date) }}">

            </div>

        </div>
        <div class="col-md-4">
            <label>Nama Customer</label>
            <input type="text" value="{{ $rab->contact_name }}" class="form-control" readonly>
        </div>
    </div>
  
    <div class="row mb-4 mt-3">
        <div class="rab-detail-header mb-3">

            <h4 class="fw-bold mb-0">
                Rincian Pekerjaan
            </h4>

            <div class="rab-action-buttons">

                {{-- <button type="button"
                        id="tombolUbahh"
                        class="btn btn-dark btn-sm">
                    ✏️ Mode Edit
                </button>

                <button type="button"
                        id="tombolGeserr"
                        class="btn btn-outline-secondary btn-sm">
                    🔀 Urutkan Daftar Pekerjaan
                </button> --}}
                <button type="button"
                        class="btn btn-dark btn-sm"
                            onclick="openEditRabItemModal()">
                    + Tambah Item
                </button>
            </div>

        </div>
        <div class="table-responsive">

            <table class="table table-bordered align-middle" id="rabItemsTable">

                <colgroup>
                    <col style="width: 60px">
                    <col style="width: 180px">
                    <col style="width: 60px">
                    <col style="width: 130px">
                    <col style="width: 180px">
                    <col style="width: 40px">
                </colgroup>

                <thead>
                    <tr>
                        <th class="text-center">NO</th>
                        <th class="text-center">Nama Produk</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Harga</th>
                        <th class="text-center">JUMLAH</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody id="rab_offerItemsBody_edit">
                    {{-- Item RAB dibuat oleh JavaScript --}}
                </tbody>

                <tfoot>

                    <tr>
                        <th colspan="4" class="text-end">
                            SUBTOTAL
                        </th>

                        <th id="rab_subtotalDisplay_edit">
                            Rp 0
                        </th>

                        <th></th>
                    </tr>

                    <tr>
                        <th colspan="4" class="text-end">
                            DISCOUNT
                        </th>

                        <th>
                            <input type="text"
                                class="form-control"
                                id="rab_discount_display_edit">

                            <input type="hidden"
                                name="discount"
                                id="rab_discount">
                        </th>

                        <th></th>
                    </tr>

                    <tr>
                        <th colspan="4" class="text-end">
                            SUBTOTAL AFTER DISCOUNT
                        </th>

                        <th id="rab_subAfterDiscountDisplay_edit">
                            Rp 0
                        </th>

                        <th></th>
                    </tr>

                    <tr>
                        <th colspan="4" class="text-end">
                            TAX RATE (%)
                        </th>

                        <th>
                            <input type="number"
                                class="form-control"
                                name="tax_rate"
                                id="rab_tax_rate_edit"
                                min="0"
                                step="0.01">
                        </th>

                        <th></th>
                    </tr>

                    <tr>
                        <th colspan="4" class="text-end">
                            TOTAL TAX
                        </th>

                        <th id="rab_totalTaxDisplay_edit">
                            Rp 0
                        </th>

                        <th></th>
                    </tr>

                    <tr>
                        <th colspan="4" class="text-end">
                            SHIPPING / HANDLING
                        </th>

                        <th>
                            <input type="text"
                                class="form-control"
                                id="rab_shipping_display_edit">

                            <input type="hidden"
                                name="shipping"
                                id="rab_shipping">
                        </th>

                        <th></th>
                    </tr>

                    <tr>
                        <th colspan="4" class="text-end">
                            GRAND TOTAL
                        </th>

                        <th id="rab_grandTotalDisplay_edit">
                            Rp 0
                        </th>

                        <th></th>
                    </tr>

                </tfoot>

            </table>

        </div>
    </div>
    <div class="modal fade" id="editRabItemModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                <div class="modal-header border-0">

                    <div>
                        <h5 class="modal-title fw-bold">
                            Tambah Item RAB
                        </h5>

                        <small class="text-muted">
                            Masukkan produk yang akan ditambahkan ke RAB
                        </small>
                    </div>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Deskripsi Pekerjaan
                        </label>

                        <div id="edit-description-editor"></div>

                        <textarea id="rab_item_description_edit"
                                class="d-none"></textarea>

                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6 mb-3">

                            <label class="form-label required fw-semibold">
                                Qty
                            </label>

                            <input type="text"
                                id="rab_item_volume_edit"
                                class="form-control"
                                inputmode="decimal"
                                placeholder="1">

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label required fw-semibold">
                                Harga
                            </label>

                            <input type="text"
                                id="rab_item_price_display_edit"
                                class="form-control"
                                inputmode="decimal"
                                placeholder="Rp 0,00">

                            <input type="hidden"
                                id="edit_rab_item_price">

                        </div>
                    </div>
                </div>


                <div class="modal-footer border-0">

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="button"
                            class="btn btn-dark"
                            onclick="saveEditRabItem()">
                        Simpan Item
                    </button>

                </div>

            </div>

        </div>

    </div>
        <input type="hidden" name="subtotal" id="rab_subtotal" value="{{ $rab->subtotal }}">
        <input type="hidden" name="subtotal_after_discount" id="rab_subAfterDiscount" value="{{ $rab->subtotal_after_discount }}">
        <input type="hidden" name="tax_total" id="rab_tax_total" value="{{ $rab->tax_total }}">
        <input type="hidden" name="grand_total" id="rab_grand_total" value="{{ $rab->grand_total }}">
            <div id="rabEditItemsContainer"></div>
    <h4 class="fw-bold mb-3">Keterangan</h4>

    <textarea name="notes" rows="3" class="form-control">{{ old('notes', $rab->notes) }}</textarea>
</form>

@push('js')
<script>
    window.currentRabId = "{{ $rab->id ?? '' }}";

    let enterLock = false

    document.addEventListener('keydown', function(e){

        if($(e.target).closest('.select2-container').length){
            return
        }

        if(e.key !== 'Enter') return
        if(enterLock) return

        enterLock = true

        setTimeout(() => {
            enterLock = false
        }, 300)

        const el = e.target

        if(!el || el.disabled) return

        if(el.classList.contains('uraian-input')){

            e.preventDefault()

            if(el.dataset.saving === '1') return

            el.dataset.saving = '1'

            const row = el.closest('.uraian-row')

            if(row){
                saveUraianEdit(row.id)
            }

            setTimeout(() => {
                delete el.dataset.saving
            }, 300)

            return
        }

        if(el.classList.contains('category-input')){

            e.preventDefault()

            const row = el.closest('.category-row')

            if(row){
                saveCategoryEdit(row.id)
            }

            return
        }
    })

    document.addEventListener('blur', function(e){

        const el = e.target

        if(!el.classList.contains('uraian-input')) return

        if(el.dataset.saving === '1') return

        el.dataset.saving = '1'

        const row = el.closest('.uraian-row')

        if(row){
            saveUraianEdit(row.id)
        }

        setTimeout(() => {
            delete el.dataset.saving
        }, 300)

    }, true)

    document.addEventListener('blur', function(e){

        const el = e.target

        if(!el.classList.contains('category-input')) return

        const row = el.closest('.category-row')

        if(row){
            saveCategoryEdit(row.id)
        }

    }, true)

    let isSaving = false;
    let autosaveTimer = null;
    let isDragging = false;
    let currentRabJob = null;
    let rabItems = []; 
    let rabEditItems = {}; // object, bukan array
    let currentBasePrice = 0;
    let globalProfit = 0;
    let globalOverhead = 0;
    let categoryIndex = 0;
    let rupiahEditInputsBound = false;
    let uraianGlobalIndex = 0;
    let jobIndex = 0;
    let draggedGroup = [];
    let itemCounter = 0;
    let activeUraian = null;
    let currentMode = 'edit';
    let sortableInstance = null;
    let globalIndex = 0;
    let draftLoaded = false;
    let isLoadingDraft = false;
    let editrabDescriptionEditor = null;

    document.addEventListener('DOMContentLoaded', function () {

        editrabDescriptionEditor = new Quill('#edit-description-editor', {
            theme: 'snow',

            placeholder: 'Tuliskan deskripsi produk...',

            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [
                        { list: 'ordered' },
                        { list: 'bullet' }
                    ],
                    ['link'],
                    ['clean']
                ]
            }
        });
    const offerDate =
        document.getElementById('offer_date');

    if (offerDate) {

        flatpickr(offerDate, {

            dateFormat: 'Y-m-d',

            altInput: true,

            altFormat: 'd/m/Y',

            allowInput: true,

            defaultDate:
                offerDate.value || new Date(),

        });

    }
    });
    function initRabEdit(){

        $('.select2-row').each(function(){
            if($(this).hasClass("select2-hidden-accessible")){
                $(this).select2('destroy');
            }
        });

        $('.select2-row').select2({
            width: '100%',
            dropdownAutoWidth: true
        });

        if (!rupiahEditInputsBound) {
            initRupiahInputsEdit();
            rupiahEditInputsBound = true;
        }

        recalcAfterDrag();
    }
    function initRupiahInputsEdit() {

    const discountInput =
        document.getElementById('rab_discount_display_edit');

    if (discountInput) {

        discountInput.addEventListener('input', function () {
            document.getElementById('rab_discount').value =
                parseRupiah(this.value);
            rabEditCalculateSummary();
        });

        discountInput.addEventListener('blur', function () {
            const value = parseRupiah(this.value);
            this.value = value > 0 ? formatRupiah(value) : '';
        });
    }

    const shippingInput =
        document.getElementById('rab_shipping_display_edit');

    if (shippingInput) {

        shippingInput.addEventListener('input', function () {
            document.getElementById('rab_shipping').value =
                parseRupiah(this.value);
            rabEditCalculateSummary();
        });

        shippingInput.addEventListener('blur', function () {
            const value = parseRupiah(this.value);
            this.value = value > 0 ? formatRupiah(value) : '';
        });
    }

    const taxInput =
        document.getElementById('rab_tax_rate_edit');

    if (taxInput) {
        taxInput.addEventListener('input', rabEditCalculateSummary);
    }
}
    function parseRupiah(val) {

        if (!val) return 0;

        val = String(val)
            .replace(/[^\d.,]/g, '');

        val = val.replace(/\./g, '');

        val = val.replace(',', '.');

        return Number(val) || 0;
    }

    function formatRupiah(value) {
        value = Number(value) || 0;

        return 'Rp ' + new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 3,
            maximumFractionDigits: 3
        }).format(value);
    }

    function rupiahInput(el){

        let number = parseRupiah(el.value)

        if(isNaN(number)) number = 0

        el.dataset.value = number

        el.value = number
            ? formatRupiah(number)
            : ''

    }
    function parsePercent(value){

        if(!value) return 0

        return Number(
            value
            .toString()
            .replace(',', '.')
            .replace('%','')
        )

    }
        function parseDecimal(value) {
        if (value === null || value === undefined || value === '') {
            return 0;
        }

        let str = String(value)
            .trim()
            .replace(/\s/g, '');

        if (str.includes(',')) {
            str = str.replace(/\./g, '');
            str = str.replace(',', '.');
        }

        return parseFloat(str) || 0;
    }
    function numberToLetters(num){
        let letters = ''
        num = num + 1 // karena A = 1, bukan 0

        while(num > 0){
            let rem = (num - 1) % 26
            letters = String.fromCharCode(65 + rem) + letters
            num = Math.floor((num - 1) / 26)
        }

        return letters
    }
    function round(num){
        return Math.round(num)
    }

    function setModeEdit(mode) {

        currentMode = mode;


        const btnEdit =
            document.getElementById(
                'tombolUbahh'
            );

        const btnDrag =
            document.getElementById(
                'tombolGeserr'
            );


        if (btnEdit) {

            btnEdit.classList.toggle(
                'btn-dark',
                mode === 'edit'
            );

            btnEdit.classList.toggle(
                'btn-outline-secondary',
                mode !== 'edit'
            );

        }


        if (btnDrag) {

            btnDrag.classList.toggle(
                'btn-dark',
                mode === 'drag'
            );

            btnDrag.classList.toggle(
                'btn-outline-secondary',
                mode !== 'drag'
            );

        }


        if (mode === 'drag') {

            document.body.classList.add(
                'drag-mode'
            );

        } else {

            document.body.classList.remove(
                'drag-mode'
            );

        }


        updateSortableEdit();

    }

    function updateSortableEdit() {

        if (sortableInstance) {
            sortableInstance.destroy();
            sortableInstance = null;
        }

        if (currentMode !== 'drag') {
            return;
        }

        const tbody = document.getElementById(
            'rab_offerItemsBody_edit'
        );

        if (!tbody) return;


        sortableInstance = new Sortable(tbody, {

            animation: 150,

            handle: '.drag-handle',

            draggable: '.category-row, .job-row',

            ghostClass: 'sortable-ghost',

            chosenClass: 'sortable-chosen',

            dragClass: 'sortable-drag',

    onMove: function (evt) {

        const dragged = evt.dragged;
        const related = evt.related;

        if (!dragged) {
            return false;
        }

        // =========================
        // DRAG CATEGORY
        // =========================
        if (
            dragged.classList.contains('category-row')
        ) {

            // Category hanya boleh bertemu category
            if (
                !related ||
                !related.classList.contains('category-row')
            ) {
                return false;
            }

            const draggedFloor =
                dragged.dataset.floor || '';

            const relatedFloor =
                related.dataset.floor || '';

            // Tidak boleh pindah antar lantai
            if (
                draggedFloor !== relatedFloor
            ) {
                return false;
            }

            return true;
        }


        // =========================
        // DRAG JOB
        // =========================
        if (
            dragged.classList.contains('job-row')
        ) {

            if (!related) {
                return false;
            }

            // Job hanya boleh bertukar dengan job
            if (
                related.classList.contains('job-row')
            ) {

                const draggedCategory =
                    dragged.dataset.categoryId || '';

                const relatedCategory =
                    related.dataset.categoryId || '';

                return (
                    draggedCategory ===
                    relatedCategory
                );
            }

            // Jangan izinkan job masuk category lain
            return false;
        }

        return false;
    },

    onEnd: function (evt) {

        const dragged = evt.item;

        // Jika category yang dipindahkan,
        // pindahkan seluruh blok category + job
        if (
            dragged.classList.contains('category-row')
        ) {

            moveCategoryBlock(
                tbody,
                dragged
            );
        }

        // 1. Sinkronkan data dari DOM
        syncRabEditItemsFromDOM(
            tbody
        );

        updateEditJobNumbers(tbody);

    }

        });
    }
    function moveCategoryBlock(tbody, categoryRow) {

        if (!tbody || !categoryRow) {
            return;
        }

        const categoryId =
            categoryRow.id;

        const jobs = Array.from(
            tbody.querySelectorAll('.job-row')
        ).filter(job => {

            return (
                job.dataset.categoryId ===
                categoryId
            );

        });

        if (!jobs.length) {
            return;
        }

        let insertAfter =
            categoryRow;

        jobs.forEach(job => {

            insertAfter.after(job);

            insertAfter = job;

        });
    }
    function syncRabEditItemsFromDOM(tbody) {

        if (!tbody) return;

        let currentFloor = null;
        let currentCategoryId = null;
        let currentCategoryName = null;
        let jobOrder = 0;

        const rows = tbody.querySelectorAll(
            '.floor-row, .category-row, .job-row'
        );

        rows.forEach(row => {

            // =========================
            // FLOOR
            // =========================
            if (
                row.classList.contains('floor-row')
            ) {

                currentFloor =
                    row.dataset.floor || '';

                currentCategoryId = null;
                currentCategoryName = null;

                jobOrder = 0;

                return;
            }


            // =========================
            // CATEGORY
            // =========================
            if (
                row.classList.contains('category-row')
            ) {

                currentCategoryId =
                    row.id;

                currentCategoryName =
                    row.dataset.category || '';

                jobOrder = 0;

                return;
            }


            // =========================
            // JOB
            // =========================
            if (
                row.classList.contains('job-row')
            ) {

                const jobId =
                    row.id;

                if (
                    !rabEditItems ||
                    typeof rabEditItems !== 'object' ||
                    !rabEditItems[jobId]
                ) {
                    return;
                }

                jobOrder++;

                const item =
                    rabEditItems[jobId];

                item.floor_name =
                    currentFloor ||
                    row.dataset.floor ||
                    item.floor_name ||
                    '';

                item.category_name =
                    currentCategoryName ||
                    row.dataset.category ||
                    item.category_name ||
                    '';

                item.category_id =
                    currentCategoryId ||
                    row.dataset.categoryId ||
                    item.category_id ||
                    null;

                item.order_no =
                    jobOrder;

                row.dataset.order =
                    jobOrder;
            }

        });
    }
    function updateEditJobNumbers(tbody) {

        if (!tbody) return;

        const categories =
            tbody.querySelectorAll('.category-row');

        categories.forEach(categoryRow => {

            let number = 0;

            let row =
                categoryRow.nextElementSibling;

            while (
                row &&
                row.classList.contains('job-row')
            ) {

                number++;

                const cell =
                    row.querySelector(
                        'td:first-child'
                    );

                if (cell) {

                    const handle =
                        cell.querySelector(
                            '.drag-handle'
                        );

                    if (handle) {

                        // Cari text node setelah drag handle
                        cell.childNodes.forEach(node => {

                            if (
                                node.nodeType ===
                                Node.TEXT_NODE
                            ) {

                                node.textContent =
                                    ` ${number}`;
                            }

                        });

                    }
                }

                row =
                    row.nextElementSibling;
            }

        });
    }
    function loadExistingRab(data) {

        const tbody = document.getElementById('rab_offerItemsBody_edit');

        if (!tbody) return;

        tbody.innerHTML = '';

        globalProfit = parseFloat(data.meta?.profit) || 0;
        globalOverhead = parseFloat(data.meta?.overhead) || 0;

        const profitInput = document.getElementById('rab_profit_display_edit');
        if (profitInput) {
            profitInput.value = globalProfit;
        }

        const overheadInput = document.getElementById('rab_overhead_display_edit');
        if (overheadInput) {
            overheadInput.value = globalOverhead;
        }

        const discount = parseFloat(data.meta?.discount) || 0;

        const discountInput = document.getElementById('rab_discount');
        if (discountInput) {
            discountInput.value = discount;
        }

        const discountDisplay = document.getElementById('rab_discount_display_edit');

        if (discountDisplay) {
            discountDisplay.value =
                discount > 0
                    ? formatRupiah(discount)
                    : '';
        }

        const taxRate = parseFloat(data.meta?.tax_rate) || 0;

        const taxInput = document.getElementById('rab_tax_rate_edit');

        if (taxInput) {
            taxInput.value = taxRate;
        }

        const shipping = parseFloat(data.meta?.shipping) || 0;

        const shippingInput = document.getElementById('rab_shipping');

        if (shippingInput) {
            shippingInput.value = shipping;
        }

        const shippingDisplay =
            document.getElementById('rab_shipping_display_edit');

        if (shippingDisplay) {
            shippingDisplay.value =
                shipping > 0
                    ? formatRupiah(shipping)
                    : '';
        }

        const items = Array.isArray(data.items)
            ? [...data.items]
            : [];

        items.sort((a, b) =>
            (a.order_no ?? 0) - (b.order_no ?? 0)
        );

        const floorGroups = {};

        items.forEach(item => {

            const floor = item.floor_name?.trim() || '';
            const category = item.category_name?.trim() || '';

            const floorKey = floor || '__NO_FLOOR__';
            const categoryKey = category || '__NO_CATEGORY__';

            if (!floorGroups[floorKey]) {
                floorGroups[floorKey] = {
                    name: floor,
                    categories: {}
                };
            }

            if (!floorGroups[floorKey].categories[categoryKey]) {
                floorGroups[floorKey].categories[categoryKey] = {
                    name: category,
                    items: []
                };
            }

            floorGroups[floorKey]
                .categories[categoryKey]
                .items
                .push(item);
        });


        let categoryIndex = 0;
        let maxJobIndex = 0;

        Object.entries(floorGroups).forEach(
            ([floorKey, floorData]) => {

                const floorName = floorData.name;
                const categories = floorData.categories;

                const hasFloor = !!floorName;

                let floorId = null;

                if (hasFloor) {

                    floorId =
                        'floor_' +
                        categoryIndex +
                        '_' +
                        Date.now();

                    tbody.insertAdjacentHTML(
                        'beforeend',
                        `
                        <tr
                            class="table-secondary fw-bold floor-row"
                            id="${floorId}"
                            data-floor="${escapeHtml(floorName)}"
                        >
                            <td colspan="7">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span>
                                            ${escapeHtml(floorName)}
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        `
                    );
                }


                let categoryLetterIndex = 0;

                Object.entries(categories).forEach(
                    ([categoryKey, categoryData]) => {

                        const categoryName = categoryData.name;
                        const categoryItems = categoryData.items;

                        const hasCategory = !!categoryName;

                        let categoryId = null;

                        if (hasCategory) {

                            categoryId =
                                'cat_' +
                                categoryIndex++;

                            const categoryLetter = numberToLetters(categoryLetterIndex);

                            const categoryTotal =
                                categoryItems.reduce(
                                    (sum, item) =>
                                        sum +
                                        (parseFloat(item.total) || 0),
                                    0
                                );

                            tbody.insertAdjacentHTML(
                                'beforeend',
                                `
                                <tr
                                    class="table-secondary fw-bold category-row"
                                    id="${categoryId}"
                                    data-category="${escapeHtml(categoryName)}"
                                    data-floor="${escapeHtml(floorName)}"
                                >
                                    <td>
                                        <span class="drag-handle me-2">
                                            <i class="ti ti-grip-vertical"></i>
                                        </span>
                                        ${categoryLetter}
                                    </td>

                                    <td colspan="4">
                                        <span class="category-text">
                                            ${escapeHtml(categoryName)}
                                        </span>
                                    </td>

                                    <td>
                                        <input
                                            class="form-control subtotal-category"
                                            data-category="${categoryId}"
                                            value="${formatRupiah(categoryTotal)}"
                                            readonly
                                        >
                                    </td>

                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-secondary"
                                            onclick="removeCat('${categoryId}')"
                                        >
                                            -
                                        </button>
                                    </td>
                                </tr>
                                `
                            );

                            categoryLetterIndex++;
                        }

                        let itemNo = 1;

                        categoryItems.forEach(item => {

                            const jobId = 'job_' + item.id;

                            if (Number(item.id) > maxJobIndex) {
                                maxJobIndex = Number(item.id);
                            }

                            const volume = parseFloat(item.volume) || 0;

                            const basePrice = parseFloat(item.base_price) || 0;

                            const price = parseFloat(item.price) || 0;

                            const total = parseFloat(item.total) || 0;

                            tbody.insertAdjacentHTML(
                                'beforeend',
                                `
                                <tr
                                    class="job-row"
                                    id="${jobId}"
                                    data-id="${item.id}"
                                    data-floor="${escapeHtml(floorName)}"
                                    data-category="${escapeHtml(categoryName)}"
                                    data-category-id="${categoryId ?? ''}"
                                    data-order="${item.order_no ?? 0}"
                                >

                                    <td class="text-center">
                                        ${itemNo}
                                    </td>

                                    <td>
                                        <div class="rab-description-preview">
                                            ${item.description ?? ''}
                                        </div>
                                    </td>

                                    <td>

                                        <input
                                            type="number"
                                            class="form-control vol"
                                            step="0.00001"
                                            value="${volume}"
                                            oninput="rabEditCalculate('${jobId}')"
                                        >

                                    </td>

                                    <td>

                                        <input
                                            type="text"
                                            class="form-control harga"
                                            value="${formatRupiah(price)}"
                                            data-base-price="${basePrice}"
                                            oninput="rabEditPriceInput('${jobId}')"
                                            onblur="formatRabEditPrice('${jobId}')"
                                        >

                                    </td>

                                    <td>

                                        <input
                                            type="text"
                                            class="form-control total"
                                            data-value="${total}"
                                            value="${formatRupiah(total)}"
                                            readonly
                                        >

                                    </td>

                                    <td>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-dark"
                                            onclick="addJobRowEdit('${categoryId ?? ''}')"
                                        >
                                            +
                                        </button>

                                        <button
                                            type="button"
                                            class="btn btn-sm btn-secondary"
                                            onclick="removeJob('${jobId}')"
                                        >
                                            -
                                        </button>

                                    </td>

                                </tr>
                                `
                            );

                            rabEditItems[jobId] = {
                                id: item.id,
                                floor_name: floorName,
                                category_name: categoryName,
                                description: item.description ?? '',
                                volume: volume,
                                base_price: basePrice,
                                harga: price,
                                total: total,
                                order_no: item.order_no ?? 0
                            };
                            itemNo++;
                        });

                    });
            });

        jobIndex = maxJobIndex + 1;

        setTimeout(() => {
            rabEditCalculateSummary();
        }, 100);
    }

    function escapeHtml(value) {

        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    }
    function addCategoryEdit(){
        if(isDragMode()) return
        const tbody = document.getElementById('rab_offerItemsBody_edit')

        let letter = numberToLetters(categoryIndex)
        let catId = 'cat_'+categoryIndex

        uraianIndex[catId] = 1

        tbody.insertAdjacentHTML('beforeend',`

        <tr class="table-secondary fw-bold category-row editing" id="${catId}" data-category="${catId}">
            <td>
                <span class="drag-handle me-2" style="cursor:move">
                    <i class="ti ti-grip-vertical"></i>
                </span>
                ${letter}
            </td>

            <td colspan="5">
                <input type="text" class="form-control fw-bold category-input"
                    placeholder="Nama kategori pekerjaan">
            </td>

            <td></td>
        </tr>

        <tr class="no-drag" id="addUraianEdit_${catId}">
            <td></td>
            <td colspan="6">
                <button type="button" class="btn btn-sm btn-link"
                    onclick="addJobRowEdit('${catId}')">
                    + Tambah Pekerjaan
                </button>
            </td>
        </tr>
        `)

        categoryIndex++
    }

    function saveCategoryEdit(catId){

        const row = document.getElementById(catId)
        let input = row.querySelector('.category-input')

        row.classList.remove('editing')

        let name

        if(input){
            name = input.value.trim()
        }else{
            // mode edit ulang
            input = row.querySelector('.category-text')
            name = input.innerText.trim()
        }

        if(!name){
            alert('Nama kategori tidak boleh kosong')
            return
        }

        row.dataset.name = name

        // SIMPAN huruf kategori dulu
        const letter = row.cells[0].innerText.trim()

        row.innerHTML = `
            <td>
                <span class="drag-handle me-2" style="cursor:move">
                    <i class="ti ti-grip-vertical"></i>
                </span>
                ${letter}
            </td>


            <td colspan="4" class="fw-bold">

                <span class="category-text"
                    onclick="editCategory('${catId}')">

                    ${name}

                </span>

            </td>

            <td>
                <input type="text"
                    class="form-control subtotal-category"
                    data-category="${catId}"
                    value="Rp 0"
                    readonly>
            </td>

            <td>
                <button type="button" class="btn btn-sm btn-secondary"
                    onclick="removeCat('${catId}')">
                    -
                </button>
            </td>
        `
    }

    function editCategory(catId){

        const row = document.getElementById(catId)
        row.classList.add('editing')

        const name = row.dataset.name || ''
        const letter = row.cells[0].innerText.trim()

        row.innerHTML = `
            <td>
                <span class="drag-handle me-2">
                    <i class="ti ti-grip-vertical"></i>
                </span>
                ${letter}
            </td>

            <td colspan="5">

                <input type="text"
                    class="form-control fw-bold category-input"
                    value="${name}">

            </td>

            <td></td>
        `

        setTimeout(()=>{
            row.querySelector('.category-input').focus()
        },50)

    }

    function addJobRowEdit(catId){

        const idx = jobIndex++
        const jobId = 'job_' + idx

        const categoryRow = document.getElementById(catId)
        if(!categoryRow) return

        const categoryName = categoryRow.dataset.category || ''
        const floorName = categoryRow.dataset.floor || ''

        const relatedRows = [
            ...document.querySelectorAll(`.job-row[data-category-id="${catId}"]`)
        ]

        const lastRow = relatedRows.length
            ? relatedRows[relatedRows.length - 1]
            : categoryRow

        lastRow.insertAdjacentHTML('afterend', `
        <tr class="job-row"
            id="${jobId}"
            data-id=""
            data-floor="${escapeHtml(floorName)}"
            data-category="${escapeHtml(categoryName)}"
            data-category-id="${catId}"
            data-order="0">

            <td class="text-center">${relatedRows.length + 1}</td>

            <td>
                <input type="text"
                    class="form-control job-name"
                    placeholder="Nama pekerjaan">
            </td>

            <td>
                <input type="text"
                    class="form-control sat"
                    placeholder="m2">
            </td>

            <td>
                <input type="number"
                    class="form-control vol"
                    step="0.00001"
                    oninput="rabEditCalculate('${jobId}')">
            </td>

            <td>
                <input type="text"
                    class="form-control harga"
                    data-base-price="0"
                    oninput="rabEditPriceInput('${jobId}')"
                    onblur="formatRabEditPrice('${jobId}')">
            </td>

            <td>
                <input type="text"
                    class="form-control total"
                    data-value="0"
                    readonly>
            </td>

            <td>
                <button type="button"
                    class="btn btn-sm btn-dark"
                    onclick="addJobRowEdit('${catId}')">+</button>
                <button type="button"
                    class="btn btn-sm btn-secondary"
                    onclick="removeJob('${jobId}')">-</button>
            </td>
        </tr>
        `)
    }
    function rabEditPriceInput(rowId) {

        const row = document.getElementById(rowId);

        if (!row) return;

        const hargaInput = row.querySelector('.harga');

        if (!hargaInput) return;

        const basePrice = parseRupiah(hargaInput.value);

        hargaInput.dataset.basePrice = basePrice;

        rabEditCalculate(rowId);
    }
    function formatRabEditPrice(rowId) {

        const row = document.getElementById(rowId);

        if (!row) return;

        const hargaInput =
            row.querySelector('.harga');

        if (!hargaInput) return;

        const basePrice =
            parseRupiah(hargaInput.value);

        hargaInput.dataset.basePrice =
            basePrice;

        hargaInput.value =
            basePrice
                ? formatRupiah(basePrice)
                : '';

        rabEditCalculate(rowId);
    }
    function rabEditCalculate(rowId, triggerSave = true) {

        const row = document.getElementById(rowId);

        if (!row) return;

        const vol =
            Number(row.querySelector('.vol')?.value) || 0;

        const hargaInput =
            row.querySelector('.harga');

        const totalEl =
            row.querySelector('.total');

        if (!hargaInput || !totalEl) return;

        const basePrice =
            Number(hargaInput.dataset.basePrice) || 0;

        const profitValue =
            basePrice * (globalProfit / 100);

        const overheadValue =
            basePrice * (globalOverhead / 100);

        const hargaFinal =
            basePrice +
            profitValue +
            overheadValue;

        const total =
            vol * hargaFinal;

        // JANGAN ubah hargaInput.value di sini

        totalEl.dataset.value = total;
        totalEl.value = formatRupiah(total);

        rabEditItems[rowId] = {
            ...(rabEditItems[rowId] || {}),

            volume: vol,
            base_price: basePrice,
            harga: hargaFinal,
            total: total
        };

        updateCategorySubtotal(
            row.dataset.categoryId
        );

        if (triggerSave) {
            rabEditCalculateSummary();
        }
    }
    function updateCategorySubtotal(catId){

        let subtotal = 0
        document.querySelectorAll(`.job-row[data-category-id="${catId}"]`)
        .forEach(row=>{

            const totalInput = row.querySelector('.total')

            subtotal += Number(totalInput.dataset.value || 0)

        })

        const subtotalInput = document.querySelector(
            `.subtotal-category[data-category="${catId}"]`
        )

        if(subtotalInput){

            subtotalInput.dataset.value = subtotal
            subtotalInput.value = formatRupiah(subtotal)

        }

    }
function rabEditCalculateSummary(){

    let subtotal = 0;

    document.querySelectorAll('#rab_offerItemsBody_edit .total').forEach(el=>{
        subtotal += Number(el.dataset.value || 0);
    });

    document.getElementById('rab_subtotal').value = subtotal;
    document.getElementById('rab_subtotalDisplay_edit').innerText = formatRupiah(subtotal);

    let discount = Number(document.getElementById('rab_discount').value || 0);

    let subAfterDiscount = Math.max(0, subtotal - discount);

    document.getElementById('rab_subAfterDiscount').value = subAfterDiscount;
    document.getElementById('rab_subAfterDiscountDisplay_edit').innerText = formatRupiah(subAfterDiscount);

    let taxRate = Number(document.getElementById('rab_tax_rate_edit').value || 0);

    let taxTotal = round(subAfterDiscount * taxRate / 100);

    document.getElementById('rab_tax_total').value = taxTotal;
    document.getElementById('rab_totalTaxDisplay_edit').innerText = formatRupiah(taxTotal); // fix suffix

    let shipping = Number(document.getElementById('rab_shipping').value || 0);

    let grand = subAfterDiscount + taxTotal + shipping;

    const grandEl = document.getElementById('rab_grandTotalDisplay_edit');
    grandEl.dataset.value = grand;
    grandEl.innerText = formatRupiah(grand);

    document.getElementById('rab_grand_total').value = grand;
}
function removeJob(id){
    const row = document.getElementById(id);
    if (!row) return;

    delete rabEditItems[id]; // tambahkan ini, sebelumnya belum ada malah

    const catId = row.dataset.categoryId || null;
    row.remove();

    if (catId) {
        updateCategorySubtotal(catId);
        renumberUraian(catId);
    }

    rabEditCalculateSummary();
}
    function removeUraianEdit(id){
        const row = document.getElementById(id)
        if(!row) return

        const catId = row.dataset.category

        document.querySelectorAll(`[data-parent="${id}"]`).forEach(e=>e.remove())

        row.remove()

        renumberUraian(catId)

        updateCategorySubtotal(catId)

        rabEditCalculateSummary()
    }
    function removeCat(catId){
        const catRow = document.getElementById(catId)
        if(!catRow) return
        document.querySelectorAll(`.job-row[data-category-id="${catId}"]`)
        .forEach(job => {
            delete rabEditItems[job.id]
            job.remove()
        })

        const addRow = document.getElementById('addUraianEdit_'+catId)
        if(addRow) addRow.remove()

        catRow.remove()

        renumberCategory()
        rabEditCalculateSummary()
    }
    function renumberCategory(){

        const categories = document.querySelectorAll('.category-row')

        categories.forEach((cat,i)=>{

            const letter = numberToLetters(i)

            cat.querySelector('td').innerHTML = `
                <span class="drag-handle me-2" style="cursor:move">
                    <i class="ti ti-grip-vertical"></i>
                </span>
                ${letter}
            `
        })

        categoryIndex = categories.length
    }
    function renumberUraian(catId){
        let rows = document.querySelectorAll(`.job-row[data-category-id="${catId}"]`)
        rows.forEach((row, i) => {
            row.querySelector('td').innerText = i + 1
        })
    }
    function renumberAll(){

        document.querySelectorAll('.category-row').forEach((cat, i)=>{

            const catId = cat.id;

            // 🔥 renumber kategori (A, B, C)
            const letter = numberToLetters(i)

            cat.querySelector('td').innerHTML = `
                <span class="drag-handle me-2" style="cursor:move">
                    <i class="ti ti-grip-vertical"></i>
                </span>
                ${letter}
            `

            // 🔥 renumber uraian
            const uraianRows = document.querySelectorAll(`.uraian-row[data-category="${catId}"]`)

            uraianRows.forEach((row, index)=>{
                row.querySelector('td:first-child').innerText = index + 1
            })

            uraianIndex[catId] = uraianRows.length + 1
        })

        categoryIndex = document.querySelectorAll('.category-row').length
    }
    function recalcAfterDrag(){

        document.querySelectorAll('.job-row').forEach(row=>{
            rabEditCalculate(row.id, false)
        })
        rabEditCalculateSummary()
    }
    function getRabFloorsEdit() {
        const values = Object.values(rabEditItems);

        return [
            ...new Set(
                values
                    .map(item => item.floor_name)
                    .filter(value => value && value.trim() !== '')
            )
        ];
    }

    function getRabCategoriesEdit(floor = null) {

        let values = Object.values(rabEditItems);

        if (floor) {
            values = values.filter(item => item.floor_name === floor);
        }

        return [
            ...new Set(
                values
                    .map(item => item.category_name)
                    .filter(value => value && value.trim() !== '')
            )
        ];
    }
    function renderFloorOptionsEdit(selectedValue = '') {

        const select =
            document.getElementById('rab_item_floor_edit');

        if (!select) return;

        const floors = getRabFloorsEdit();

        select.innerHTML = `
            <option value="">
                -- Pilih Lantai --
            </option>
        `;

        // Lantai yang sudah pernah digunakan
        floors.forEach(floor => {

            const option =
                document.createElement('option');

            option.value = floor;
            option.textContent = floor;

            select.appendChild(option);

        });

        // Selalu tampilkan pilihan tambah lantai
        const newOption =
            document.createElement('option');

        newOption.value = '__new__';
        newOption.textContent = '+ Tambah Lantai Baru';

        select.appendChild(newOption);


        if (selectedValue) {

            select.value = selectedValue;

        }

    }
    function renderCategoryOptionsEdit(floor = null) {

        const select =
            document.getElementById('rab_item_category_edit');

        if (!select) return;

        const categories =
            getRabCategoriesEdit(floor);

        select.innerHTML = `
            <option value="">
                -- Pilih Kategori --
            </option>
        `;

        categories.forEach(category => {

            const option =
                document.createElement('option');

            option.value = category;
            option.textContent = category;

            select.appendChild(option);

        });

        const newOption =
            document.createElement('option');

        newOption.value = '__new__';
        newOption.textContent =
            '+ Tambah Kategori Baru';

        select.appendChild(newOption);
    }
    function handleFloorChangeEdit() {

        const select =
            document.getElementById('rab_item_floor_edit');

        const value = select.value;

        if (value === '__new__') {

            showNewFloorInputEdit();

            return;

        }

        renderCategoryOptionsEdit(value);

    }
    function handleCategoryChange() {
        const select = document.getElementById('rab_item_category_edit');
        const value = select.value;

        if (value === '__new__') {
            showNewCategoryInputEdit();
            return;
        }
        // kategori dipilih biasa, tidak perlu aksi tambahan
    }
function showNewFloorInputEdit() {

    document
        .getElementById('floorSelectWrapperEdit')
        .classList.add('d-none');

    document
        .getElementById('floorInputWrapperEdit')
        .classList.remove('d-none');

    document
        .getElementById('rab_item_floor_news')
        .value = '';

    document
        .getElementById('rab_item_floor_news')
        .focus();
}
function cancelNewFloorEdit() {

    document
        .getElementById('floorInputWrapperEdit')
        .classList.add('d-none');

    document
        .getElementById('floorSelectWrapperEdit')
        .classList.remove('d-none');

    renderFloorOptionsEdit();
}
    function showNewCategoryInputEdit() {

        document
            .getElementById('categorySelectWrapperEdit')
            .classList.add('d-none');

        document
            .getElementById('categoryInputWrapperEdit')
            .classList.remove('d-none');

        document
            .getElementById('rab_item_category_news')
            .value = '';

        document
            .getElementById('rab_item_category_news')
            .focus();

    }
    function cancelNewCategoryEdit() {

        document
            .getElementById('categoryInputWrapperEdit')
            .classList.add('d-none');

        document
            .getElementById('categorySelectWrapperEdit')
            .classList.remove('d-none');

        const floor =
            document.getElementById('rab_item_floor_edit').value;

        renderCategoryOptionsEdit(floor);

    }
function getSelectedFloorEdit() {

    const select =
        document.getElementById('rab_item_floor_edit');

    const newInput =
        document.getElementById('rab_item_floor_news');

    const inputWrapper =
        document.getElementById('floorInputWrapperEdit');

    if (
        inputWrapper &&
        !inputWrapper.classList.contains('d-none')
    ) {
        return newInput
            ? newInput.value.trim()
            : '';
    }

    return select
        ? select.value.trim()
        : '';
}
function getSelectedCategoryEdit() {

    const select =
        document.getElementById('rab_item_category_edit');

    const newInput =
        document.getElementById('rab_item_category_news');

    const inputWrapper =
        document.getElementById('categoryInputWrapperEdit');

    if (
        inputWrapper &&
        !inputWrapper.classList.contains('d-none')
    ) {
        return newInput
            ? newInput.value.trim()
            : '';
    }

    return select
        ? select.value.trim()
        : '';
}
function openEditRabItemModal(jobId) {

    const item = rabEditItems[jobId];

    if (!item) {
        console.error('Item RAB tidak ditemukan:', jobId);
        return;
    }
    const modalElement =
        document.getElementById('editRabItemModal');

    modalElement.dataset.jobId = jobId;

    if (editrabDescriptionEditor) {

        editrabDescriptionEditor.clipboard.dangerouslyPasteHTML(
            item.description || ''
        );

    }

    document.getElementById(
        'rab_item_description_edit'
    ).value = item.description || '';

    document.getElementById(
        'rab_item_volume_edit'
    ).value = item.volume ?? '';

    const price =
        document.getElementById(
            'rab_item_price_display_edit'
        );

    const numericPrice =
        parseFloat(item.harga) || 0;

    price.value =
        numericPrice > 0
            ? formatRupiah(numericPrice)
            : '';

    price.dataset.value = numericPrice;

    document.getElementById(
        'edit_rab_item_price'
    ).value = numericPrice;

    document.querySelector(
        '#editRabItemModal .modal-title'
    ).textContent = 'Edit Item RAB';

    bootstrap.Modal
        .getOrCreateInstance(modalElement)
        .show();
}
    
function saveEditRabItem() {

    const floor = getSelectedFloorEdit();
    const category = getSelectedCategoryEdit();

    const description = document.getElementById('rab_item_description_edit').value.trim();
    const volume = parseDecimal(document.getElementById('rab_item_volume_edit').value);
    const basePrice = parseRupiah(document.getElementById('rab_item_price_display_edit').value);

    if (!floor) { alert('Lantai wajib diisi.'); return; }
    if (!category) { alert('Kategori pekerjaan wajib diisi.'); return; }
    if (!jobName) { alert('Nama pekerjaan wajib diisi.'); return; }
    if (!satuan) { alert('Satuan wajib diisi.'); return; }
    if (volume <= 0) { alert('Volume harus lebih besar dari 0.'); return; }
    if (basePrice < 0) { alert('Harga satuan tidak valid.'); return; }

    const price = calculateItemPriceEdit(basePrice); // ✅ fungsi edit
    const total = volume * price;

    const jobId = document.getElementById('rab_item_id_edit').value; // ✅ suffix _edit

    if (jobId && rabEditItems[jobId]) { // ✅ rabEditItems

        rabEditItems[jobId] = {
            ...rabEditItems[jobId],
            floor_name: floor,
            category_name: category,
            description: description,
            volume: volume,
            base_price: basePrice,
            harga: price, // ✅ konsisten "harga"
            total: total
        };

    } else {

        const newId = 'job_new_' + (++itemCounter);

        rabEditItems[newId] = { // ✅ rabEditItems
            id: null,
            floor_name: floor,
            category_name: category,
            job_name: jobName,
            description: description,
            satuan: satuan,
            volume: volume,
            base_price: basePrice,
            harga: price,
            total: total,
            order_no: Object.keys(rabEditItems).length + 1
        };
    }

    renderEditRabItems();       // pastikan fungsi ini juga baca rabEditItems
    rabEditCalculateSummary();

    const modalElement = document.getElementById('editRabItemModal');
    const modal = bootstrap.Modal.getInstance(modalElement);
    if (modal) modal.hide();
}
function calculateItemPriceEdit(basePrice) {
    basePrice = Number(basePrice) || 0;
    const profitAmount = basePrice * globalProfit / 100;
    const overheadAmount = basePrice * globalOverhead / 100;
    return basePrice + profitAmount + overheadAmount;
}
    function calculateItemPrice(basePrice) {

        basePrice = Number(basePrice) || 0;

        const profit =
            Number(
                document.getElementById('rab_profit_display_edit')?.value
            ) || 0;

        const overhead =
            Number(
                document.getElementById('rab_overhead_display_edit')?.value
            ) || 0;

        const overheadAmount =
            basePrice * overhead / 100;

        const profitAmount =
            basePrice * profit / 100;

        return basePrice
            + overheadAmount
            + profitAmount;
    }
    function recalculateAllItems() {

        rabEditItems.forEach(item => {

            const basePrice =
                Number(item.base_price) || 0;

            item.price =
                calculateItemPrice(basePrice);

            item.total =
                Number(item.volume || 0) *
                item.price;

        });

        renderRabItems();

        calculateSummary();
    }
function renderEditRabItems() {

    const tbody =
        document.getElementById('rab_offerItemsBody_edit');

    if (!tbody) return;

    tbody.innerHTML = '';

    const entries = Object.entries(rabEditItems);

    if (entries.length === 0) {

        tbody.innerHTML = `
            <tr class="empty-rab-row">
                <td colspan="7" class="text-center text-muted py-5">
                    Belum ada pekerjaan.
                </td>
            </tr>
        `;

        return;
    }

    // Kelompokkan per lantai -> kategori
    const floorGroups = {};

    entries.forEach(([jobId, item]) => {

        const floor = item.floor_name || 'Tanpa Lantai';
        const category = item.category_name || 'Tanpa Kategori';

        if (!floorGroups[floor]) floorGroups[floor] = {};
        if (!floorGroups[floor][category]) floorGroups[floor][category] = [];

        floorGroups[floor][category].push({ jobId, ...item });

    });

    let catIdx = 0;
    let categoryLetterIndex = 0;

    Object.entries(floorGroups).forEach(([floorName, categories]) => {

        const floorId = 'floor_' + catIdx + '_' + Date.now();

        tbody.insertAdjacentHTML(
            'beforeend',
            `
            <tr class="table-secondary fw-bold floor-row"
                id="${floorId}"
                data-floor="${escapeHtml(floorName)}">
                <td colspan="7">
                    <div class="d-flex align-items-center gap-2">
                        <span class="drag-handle">
                            <i class="ti ti-grip-vertical"></i>
                        </span>
                        <span>${escapeHtml(floorName)}</span>
                    </div>
                </td>
            </tr>
            `
        );

        Object.entries(categories).forEach(([categoryName, categoryItems]) => {

            const categoryId = 'cat_' + catIdx++;
            const categoryLetter = numberToLetters(categoryLetterIndex++);

            const categoryTotal = categoryItems.reduce(
                (sum, item) => sum + (Number(item.total) || 0),
                0
            );

            tbody.insertAdjacentHTML(
                'beforeend',
                `
                <tr class="table-secondary fw-bold category-row"
                    id="${categoryId}"
                    data-category="${escapeHtml(categoryName)}"
                    data-floor="${escapeHtml(floorName)}">

                    <td>
                        <span class="drag-handle me-2">
                            <i class="ti ti-grip-vertical"></i>
                        </span>
                        ${categoryLetter}
                    </td>

                    <td colspan="4">
                        <span class="category-text">
                            ${escapeHtml(categoryName)}
                        </span>
                    </td>

                    <td>
                        <input class="form-control subtotal-category"
                            data-category="${categoryId}"
                            value="${formatRupiah(categoryTotal)}"
                            readonly>
                    </td>

                    <td>
                        <button type="button"
                            class="btn btn-sm btn-secondary"
                            onclick="removeCat('${categoryId}')">
                            -
                        </button>
                    </td>
                </tr>
                `
            );

            let itemNo = 1;

            categoryItems.forEach(item => {

                const jobId = item.jobId;

                tbody.insertAdjacentHTML(
                    'beforeend',
                    `
                    <tr class="job-row"
                        id="${jobId}"
                        data-id="${item.id ?? ''}"
                        data-floor="${escapeHtml(floorName)}"
                        data-category="${escapeHtml(categoryName)}"
                        data-category-id="${categoryId}"
                        data-order="${item.order_no ?? 0}">

                        <td class="text-center">${itemNo}</td>

                        <td>
                            <input type="text"
                                class="form-control job-name"
                                value="${escapeHtml(item.job_name ?? '')}">
                        </td>

                        <td>
                            <input type="text"
                                class="form-control sat"
                                value="${escapeHtml(item.satuan ?? '')}">
                        </td>

                        <td>
                            <input type="number"
                                class="form-control vol"
                                step="0.00001"
                                value="${item.volume ?? 0}"
                                oninput="rabEditCalculate('${jobId}')">
                        </td>

                        <td>
                            <input type="text"
                                class="form-control harga"
                                value="${formatRupiah(item.harga ?? 0)}"
                                data-base-price="${item.base_price ?? 0}"
                                oninput="rabEditPriceInput('${jobId}')"
                                onblur="formatRabEditPrice('${jobId}')">
                        </td>

                        <td>
                            <input type="text"
                                class="form-control total"
                                data-value="${item.total ?? 0}"
                                value="${formatRupiah(item.total ?? 0)}"
                                readonly>
                        </td>

                        <td>
                            <button type="button"
                                class="btn btn-sm btn-dark"
                                onclick="addJobRowEdit('${categoryId}')">
                                +
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-secondary"
                                onclick="removeJob('${jobId}')">
                                -
                            </button>
                        </td>
                    </tr>
                    `
                );

                itemNo++;

            });

        });

    });
    updateSortableEdit()

}
    function updateItemVolume(id, value) {
        const item =
            rabEditItems.find(item =>
                item.temp_id === id
            );
        if (!item) return;
        item.volume = Number(value) || 0;
        item.total = item.volume * item.price;
        renderRabItems();
        calculateSummary();
    }

    function updateItemPrice(id, element) {
        const item = rabEditItems.find(item =>item.temp_id === id);
        if (!item) return;
        const basePrice = parseRupiah(element.value);
        item.base_price = basePrice;
        item.price = calculateItemPrice(basePrice);
        item.total = Number(item.volume || 0) * item.price;
        renderEditRabItems();
        calculateSummary();
    }

    function removeRabItem(id) {
        const index =
            rabEditItems.findIndex(item =>
                item.temp_id === id
            );
        if (index === -1) return;
        rabEditItems.splice(index, 1);
        normalizeOrderEdit();
        renderEditRabItems();
        calculateSummary();
    }

    function calculateSummary() {

        const subtotal = rabEditItems.reduce(
            (sum, item) => {

                return sum +
                    (Number(item.total) || 0);

            },
            0
        );

        const discount =
            parseRupiah(
                document.getElementById('rab_discount_display')?.value
            ) || 0;


        const subtotalAfterDiscount =
            Math.max(
                0,
                subtotal - discount
            );

        const taxRate =
            parseFloat(
                document.getElementById('rab_tax_rate')?.value
            ) || 0;


        const taxTotal =
            subtotalAfterDiscount *
            taxRate /
            100;

        const shipping =
            parseRupiah(
                document.getElementById('rab_shipping_display')?.value
            ) || 0;

        const grandTotal =
            subtotalAfterDiscount +
            taxTotal +
            shipping;

        document.getElementById('rab_subtotalDisplay').textContent = formatRupiah(subtotal);

        document.getElementById('rab_subAfterDiscountDisplay').textContent = formatRupiah(subtotalAfterDiscount);

        document.getElementById('rab_totalTaxDisplay').textContent = formatRupiah(taxTotal);

        document.getElementById('rab_grandTotalDisplay').textContent = formatRupiah(grandTotal);

        document.getElementById('rab_subtotal').value = subtotal;

        document.getElementById('rab_discount').value = discount;

        document.getElementById('rab_subAfterDiscount').value = subtotalAfterDiscount;

        document.getElementById('rab_tax_total').value = taxTotal;

        document.getElementById('rab_shipping').value = shipping;

        document.getElementById('rab_grand_total').value = grandTotal;
    }

    document.getElementById('rab_tax_rate_edit').addEventListener('input', function () {
        rabEditCalculateSummary()
    });

function prepareRabEditItemsForSubmit() {

    document.getElementById('rab_profit_edit').value = globalProfit;
    document.getElementById('rab_overhead_edit').value = globalOverhead;

    const container = document.getElementById('rabEditItemsContainer');

    if (!container) {
        console.error('rabEditItemsContainer tidak ditemukan');
        return;
    }


    container.innerHTML = '';

    // pastikan urutan sesuai posisi DOM saat ini (habis drag/tambah/hapus)
    recalcAfterDrag();

    let index = 0;

    document.querySelectorAll('#rab_offerItemsBody_edit .job-row').forEach(row => {

        const rowId = row.id;
        const item = rabEditItems[rowId];

        if (!item) return;

        const fields = {
            id: item.id ?? '',
            floor_name: row.dataset.floor,
            category_name: row.dataset.category,
            job_name: row.querySelector('.job-name')?.value.trim() || '',
            description: item.description || '',
            satuan: row.querySelector('.sat')?.value.trim() || '',
            volume: Number(row.querySelector('.vol')?.value) || 0,
            base_price: Number(row.querySelector('.harga')?.dataset.basePrice) || 0,
            price: item.harga ?? 0,
            total: Number(row.querySelector('.total')?.dataset.value) || 0,
            order_no: index + 1
        };

        Object.entries(fields).forEach(([key, value]) => {

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `items[${index}][${key}]`;
            input.value = value ?? '';
            container.appendChild(input);

        });

        index++;

    });

}

    document.addEventListener('DOMContentLoaded', function () {
        initRupiahInputsEdit();
        // calculateSummary();

        // const discountInput = document.getElementById('rab_discount_display_edit');
        // if (discountInput) {

        //     discountInput.addEventListener(
        //         'input',
        //         calculateSummary
        //     );

        // }

        // const taxInput = document.getElementById('rab_tax_rate_edit');

        // if (taxInput) {

        //     taxInput.addEventListener(
        //         'input',
        //         calculateSummary
        //     );

        // }

        // const shippingInput = document.getElementById('rab_shipping_display_edit');

        // if (shippingInput) {

        //     shippingInput.addEventListener(
        //         'input',
        //         calculateSummary
        //     );

        // }

        const editButton = document.getElementById('tombolUbahh');

        if (editButton) {

            editButton.addEventListener(
                'click',
                function () {

                    setModeEdit('edit');

                }
            );

        }

        const dragButton = document.getElementById('tombolGeserr');

        if (dragButton) {

            dragButton.addEventListener(
                'click',
                function () {

                    setModeEdit('drag');

                }
            );

        }

        const rabEditForm = document.getElementById('rab-edit-form');

        if (rabEditForm) {

            rabEditForm.addEventListener('submit', function () {

                prepareRabEditItemsForSubmit();

            });

        }

        renderEditRabItems();

    });
</script>
@endpush