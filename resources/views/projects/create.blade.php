@extends('tablar::page')

@section('content')

    <div class="page-header d-print-none mb-4">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col d-flex align-items-center">
                    <a href="{{ route('projects.index') }}" class="btn btn-dark d-flex align-items-center">
                        <i class="ti ti-arrow-left"></i>
                    </a>      
                        <h2 class="page-title mb-0">Tambah Proyek</h2> 
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            @include('projects.components.timeline-horizontal')
            @if($activeStep == 1)
            <div id="project" class="step-section">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body px-5 py-4">
                        <h3 class="mb-4 fw-bold">Buat Proyek Baru</h3>
                        @include('projects.steps.create-project')
                    </div>
                </div>
            </div>
            @endif
            @if($activeStep >= 2)
                    <x-collapse-card title="Proyek" target="project-body">
                        <x-slot:actions>
                            @can('ubah data proyek')
                            <div class="btn-group">
                                <button type="button" id="btn-edit-project"
                                    class="btn btn-sm btn-dark me-2"
                                    title="Edit Data">
                                    <i class="ti ti-edit"></i>
                                </button>
                            </div>
                            @endcan
                        </x-slot:actions>
                        <div id="project-view">
                            @include('projects.details.project')
                        </div>
                        <div id="project-edit" style="display:none;">
                            @include('projects.edit.project-form')    
                        </div>
                    </x-collapse-card>

                @php
                    // Peta nama level -> partial view yang menampilkannya.
                    // Nambah project_type baru dengan step yang SAMA (Penawaran Harga, Invoice)
                    // otomatis kepakai tanpa perlu ubah apapun di sini.
                    // Baru perlu nambah baris kalau ada level_name BARU yang belum pernah ada.
                    $stepViews = [
                        'Penawaran Harga' => [
                            'partial' => 'projects.steps.rab-process',
                            'action'  => '<button type="submit" form="rabForm" class="btn btn-dark" title="Simpan RAB"><i class="ti ti-device-floppy me-1"></i></button>',
                        ],
                        'Invoice' => [
                            'partial' => 'projects.steps.invoice',
                            'action'  => null,
                        ],
                    ];

                    $currentLevel = $project->levels->sortBy('level_order')
                        ->first(fn ($lvl) => $activeStep === $lvl->level_order + 1);
                @endphp

                @if($currentLevel)
                    <div id="step-{{ \Illuminate\Support\Str::slug($currentLevel->level_name) }}" class="step-section">
                        <x-collapse-card
                            title="{{ ($currentLevel->level_order + 1) . '. ' . $currentLevel->level_name }}"
                            target="step-body"
                            :sticky="false">

                            @if(isset($stepViews[$currentLevel->level_name]['action']) && $stepViews[$currentLevel->level_name]['action'])
                                <x-slot:actions>
                                    {!! $stepViews[$currentLevel->level_name]['action'] !!}
                                </x-slot:actions>
                            @endif

                            @if(isset($stepViews[$currentLevel->level_name]))
                                @include($stepViews[$currentLevel->level_name]['partial'])
                            @else
                                {{-- Jenis proyek ini punya step baru yang belum ada tampilannya.
                                     Tambahkan entry-nya di $stepViews di atas. --}}
                                <div class="alert alert-warning mb-0">
                                    Belum ada tampilan untuk step "<strong>{{ $currentLevel->level_name }}</strong>".
                                    Hubungi developer untuk menambahkan partial view-nya.
                                </div>
                            @endif
                        </x-collapse-card>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endsection

@push('js')
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%'
        });
    });
</script>

<script>
$('#province').change(function () {
var id = $(this).val();
$('#city').html('<option>Loading...</option>');
$('#district').html('<option value="">-- Pilih kecamatan --</option>');
$('#sub_district').html('<option value="">-- Pilih kelurahan --</option>');

if (id) {
$.get('/api/cities/' + id, function (data) {
$('#city').empty().append('<option value="">-- Pilih city --</option>');
$.each(data, function (i, city) {
    $('#city').append('<option value="' + city.id + '">' + city.name + '</option>');
        });
    });
    }
});

$('#city').change(function () {
var id = $(this).val();
$('#district').html('<option>Loading...</option>');
$('#sub_district').html('<option value="">-- Pilih kelurahan --</option>');

if (id) {
    $.get('/api/districts/' + id, function (data) {
        $('#district').empty().append('<option value="">-- Pilih kecamatan --</option>');
        $.each(data, function (i, district) {
            $('#district').append('<option value="' + district.id + '">' + district.name + '</option>');
                });
            });
        }
    });

$('#district').change(function () {
var id = $(this).val();
$('#sub_district').html('<option>Loading...</option>');

    if (id) {
        $.get('/api/sub_districts/' + id, function (data) {
            $('#sub_district').empty().append('<option value="">-- Pilih kelurahan --</option>');
            $.each(data, function (i, sub_district) {
                $('#sub_district').append('<option value="' + sub_district.id + '">' + sub_district.name + '</option>');
            });
        });
    }
});

$('#sub_district').change(function () {
var id = $(this).val();
$('#postal_code').html('<option>Loading...</option>');

if (id) {
    $.get('/api/postal_codes/' + id, function (data) {
        $('#postal_code').empty().append('<option value="">-- Pilih kode pos --</option>');
        $.each(data, function (i, postal_code) {
            $('#postal_code').append('<option value="' + postal_code.id + '">' + postal_code.postal_code + '</option>');
        });
    });
    }
});
</script>
<script>
function initLocationCascade(config) {
    const {
        prefix = '',
        oldProvince,
        oldCity,
        oldDistrict,
        oldSub,
        oldPostal
    } = config;

    const $province = $('#' + prefix + 'province');
    const $city = $('#' + prefix + 'city');
    const $district = $('#' + prefix + 'district');
    const $sub = $('#' + prefix + 'sub_district');
    const $postal = $('#' + prefix + 'postal_code');

    function loadCities(provinceId, selected = null, callback = null) {
        if (!provinceId) return;
        $.get(`/api/cities/${provinceId}`, function (data) {
            $city.empty().append('<option value="">-- Pilih Kota --</option>');
            data.forEach(item => {
                $city.append(`<option value="${item.id}" ${selected == item.id ? 'selected' : ''}>${item.name}</option>`);
            });
            $city.trigger('change.select2');
            if (callback) callback();
        });
    }

    function loadDistricts(cityId, selected = null, callback = null) {
        if (!cityId) return;
        $.get(`/api/districts/${cityId}`, function (data) {
            $district.empty().append('<option value="">-- Pilih Kecamatan --</option>');
            data.forEach(item => {
                $district.append(`<option value="${item.id}" ${selected == item.id ? 'selected' : ''}>${item.name}</option>`);
            });
            $district.trigger('change.select2');
            if (callback) callback();
        });
    }

    function loadSubDistricts(districtId, selected = null, callback = null) {
        if (!districtId) return;
        $.get(`/api/sub_districts/${districtId}`, function (data) {
            $sub.empty().append('<option value="">-- Pilih Kelurahan --</option>');
            data.forEach(item => {
                $sub.append(`<option value="${item.id}" ${selected == item.id ? 'selected' : ''}>${item.name}</option>`);
            });
            $sub.trigger('change.select2');
            if (callback) callback();
        });
    }

    function loadPostalCodes(subId, selected = null) {
        if (!subId) return;
        $.get(`/api/postal_codes/${subId}`, function (data) {
            $postal.empty().append('<option value="">-- Pilih Kode Pos --</option>');
            data.forEach(item => {
                $postal.append(`<option value="${item.id}" ${selected == item.id ? 'selected' : ''}>${item.postal_code}</option>`);
            });
            $postal.trigger('change.select2');
        });
    }
    $province.on('change', function () {
        loadCities(this.value);
    });

    $city.on('change', function () {
        loadDistricts(this.value);
    });

    $district.on('change', function () {
        loadSubDistricts(this.value);
    });

    $sub.on('change', function () {
        loadPostalCodes(this.value);
    });

    // Restore old()
    if (oldProvince) {
        loadCities(oldProvince, oldCity, () => {
            loadDistricts(oldCity, oldDistrict, () => {
                loadSubDistricts(oldDistrict, oldSub, () => {
                    loadPostalCodes(oldSub, oldPostal);
                });
            });
        });
    }
}
</script>


<script>
document.addEventListener('DOMContentLoaded', () => {

    const fileStores = new Map();

    document.querySelectorAll('.image-input').forEach(input => {
        fileStores.set(input, new DataTransfer());

        input.addEventListener('change', () => {
            const previewEl = document.getElementById(input.dataset.preview);
            const store     = fileStores.get(input);

            const selectedFiles = Array.from(input.files);
            input.value = '';

            selectedFiles.forEach(file => {
                store.items.add(file);

                if (file.type.startsWith("image/")) {

                    const reader = new FileReader();

                    reader.onload = e => {

                        const div = document.createElement('div');
                        div.className = 'position-relative';

                        div.innerHTML = `
                            <img src="${e.target.result}"
                                class="rounded border"
                                style="width:120px;height:120px;object-fit:cover">

                            <button type="button"
                                    class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-image">
                                ×
                            </button>
                        `;

                        div.querySelector('.remove-image').addEventListener('click', () => {
                            const index = Array.from(store.files).findIndex(
                                f => f.name === file.name && f.size === file.size
                            );

                            if (index > -1) {
                                store.items.remove(index);
                                input.files = store.files;
                            }

                            div.remove();
                        });

                        previewEl.appendChild(div);
                    };

                    reader.readAsDataURL(file);

                } else if (file.type === "application/pdf") {

                    const div = document.createElement('div');
                    div.className = 'position-relative';

                    div.innerHTML = `
                        <div class="border rounded d-flex align-items-center justify-content-center"
                            style="width:120px;height:120px;">
                            📄 PDF
                        </div>

                        <button type="button"
                                class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-image">
                            ×
                        </button>
                    `;

                    div.querySelector('.remove-image').addEventListener('click', () => {
                        const index = Array.from(store.files).findIndex(
                            f => f.name === file.name && f.size === file.size
                        );

                        if (index > -1) {
                            store.items.remove(index);
                            input.files = store.files;
                        }

                        div.remove();
                    });

                    previewEl.appendChild(div);
                }
            });

            input.files = store.files;
        });
    });
});
</script>


<script>
document.addEventListener("DOMContentLoaded", () => {

    const view = document.getElementById("project-view");
    const edit = document.getElementById("project-edit");
    const editBtn = document.getElementById("btn-edit-project");
    const cancelBtn = document.getElementById("btn-cancel-project");

    if (!view || !edit || !editBtn || !cancelBtn) return; // belum ke-render (mis. masih step 1)

    editBtn.addEventListener("click", () => {
        view.style.display = "none";
        edit.style.display = "block";
    });

    cancelBtn.addEventListener("click", () => {
        edit.style.display = "none";
        view.style.display = "block";
    });

});
</script>



<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.approve-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            Swal.fire({
                title: form.dataset.title || 'Apakah Anda yakin?',
                text: form.dataset.text || 'Proses ini akan dilanjutkan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#212529',
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Memproses...',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                    form.submit();
                }
            });
        });
    });
});
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {

        document.querySelectorAll(".btn-toggle-card").forEach(btn => {

            btn.addEventListener("click", function () {

                const target = document.querySelector(
                    this.dataset.target
                );

                if(!target) return;

                // TOGGLE
                target.classList.toggle("d-none");

                // ICON
                const icon = this.querySelector("i");

                if(target.classList.contains("d-none")){

                    icon.classList.remove("ti-chevron-up");
                    icon.classList.add("ti-chevron-down");

                }else{

                    icon.classList.remove("ti-chevron-down");
                    icon.classList.add("ti-chevron-up");

                }

            });

        });

    });
</script>
@endpush