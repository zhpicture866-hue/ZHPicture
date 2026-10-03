@extends('tablar::page')

@section('content')

<div class="page-body">
    <div class="container-xl dashboard-container">
        {{-- <div class="pt-2 pb-7 text-center">
            <h2 class="fw-bold">
                Selamat Datang {{ auth()->user()->fullname ?? 'Admin Utama' }} di Sistem Antosa Architect
            </h2>
        </div> --}}

        <div class="row g-4 mt-2">
            @can('lihat akun-akuntansi')
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 rounded-4 h-100">

                    <div class="card-header">
                        <h5 class="mb-0">💰 Finance</h5>
                    </div>

                    <div class="card-body p-5">
                        <div class="card bg-primary-lt border-0 mb-3">
                            <div class="card-body">
                                <div class="text-secondary">
                                    Total Kas & Bank
                                </div>
                                <div class="fs-1 fw-bold text-primary">
                                    Rp {{ number_format($totalCashBank,0,',','.') }}
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <h4 class="text-center">
                                {{ \Carbon\Carbon::now()->translatedFormat('F Y') }}
                            </h4>
                            <div class="col-md-4">
                                <a href="{{ route('journals.general') }}" class="text-decoration-none text-dark">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="small text-secondary">
                                            📈 Pendapatan
                                        </div>

                                        <div class="fs-4 fw-bold text-success mt-2">
                                            Rp {{ number_format($monthlyRevenue,0,',','.') }}
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-secondary">
                                        📈 Kas Masuk
                                    </div>
                                    <div class="fs-3 fw-bold text-success mt-1">
                                        Rp {{ number_format($cashInThisMonth,0,',','.') }}
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded-3 p-3 h-100">
                                    <div class="small text-secondary">
                                        📉 Kas Keluar
                                    </div>
                                    <div class="fs-3 fw-bold text-danger mt-1">
                                        Rp {{ number_format($cashOutThisMonth,0,',','.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="finance-scroll">
                            @foreach($cashAccounts as $account)
                                <div class="finance-card">
                                    <div class="finance-icon">
                                        @if(str_contains(strtolower($account['account_name']), 'bank'))
                                            🏦
                                        @else
                                            💵
                                        @endif
                                    </div>
                                    @php
                                        $words = explode(' ', $account['account_name']);

                                        $line1 = implode(' ', array_slice($words, 0, 2)); // dua kata pertama
                                        $line2 = implode(' ', array_slice($words, 2));    // sisanya
                                    @endphp

                                    <div class="finance-name">
                                        <div>{{ $line1 }}</div>

                                        @if($line2)
                                            <div class="finance-sub">{{ $line2 }}</div>
                                        @endif
                                    </div>
                                    <div class="finance-balance">
                                        Rp {{ number_format($account['balance'],0,',','.') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endcan
            {{-- @can('lihat daftar proyek')
            <div class="col-xl-6">
                <div class="card shadow-sm border-0 rounded-4 h-100">

                    <div class="card-header">
                        <h5 class="mb-0">📁 Project</h5>
                    </div>

                    <div class="card-body p-4">

                        {{-- <div class="row g-3 mb-4">

                            <div class="col-4">
                                <a href="{{ route('projects.index') }}"
                                class="text-decoration-none text-dark">

                                    <div class="border rounded-3 p-3 text-center bg-light h-100 hover-card">
                                        <div class="text-secondary small">Total Project</div>
                                        <div class="fs-2 fw-bold">
                                            {{ $totalProject }}
                                        </div>
                                    </div>

                                </a>
                            </div>

                            <div class="col-4">
                                <div class="border rounded-3 p-3 text-center bg-light">
                                    <div class="text-secondary small">Sedang Dikerjakan</div>
                                    <div class="fs-2 fw-bold text-primary">
                                        {{ $runningBuild }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-4">
                                <div class="border rounded-3 p-3 text-center bg-light">
                                    <div class="text-secondary small">Sudah Selesai</div>
                                    <div class="fs-2 fw-bold text-primary">
                                        {{ $completedBuild }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-4">
                                <a href="{{ route('projects.index', ['type' => 1]) }}"
                                    class="text-decoration-none text-dark">
                                    <div class="border rounded-3 p-3 text-center">
                                        <div class="text-secondary small">Total Desain</div>
                                        <div class="fs-3 fw-bold text-info">
                                            {{ $totalDesign }}
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-4">
                                <a href="{{ route('projects.index', ['type' => 2]) }}"
                                    class="text-decoration-none text-dark">
                                    <div class="border rounded-3 p-3 text-center">
                                        <div class="text-secondary small">Total RAB</div>
                                        <div class="fs-3 fw-bold text-warning">
                                            {{ $totalRab }}
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-4">
                                <a href="{{ route('projects.index', ['type' => 3]) }}"
                                        class="text-decoration-none text-dark">
                                    <div class="border rounded-3 p-3 text-center">
                                        <div class="text-secondary small">Total Build</div>
                                        <div class="fs-3 fw-bold text-success">
                                            {{ $totalBuild }}
                                        </div>
                                    </div>
                                </a>
                            </div>

                        </div> 
                        <div class="project-scroll mb-4">

                            <a href="{{ route('projects.index') }}"
                            class="project-card text-decoration-none text-dark">

                                <div class="project-icon">📁</div>
                                <div class="project-title">Total</div>
                                <div class="project-number">{{ $totalProject }}</div>

                            </a>

                            <div class="project-card">

                                <div class="project-icon">🚧</div>
                                <div class="project-title">Sedang Dikerjakan</div>
                                <div class="project-number text-primary">
                                    {{ $runningBuild }}
                                </div>

                            </div>

      
                            <div class="project-card">

                                <div class="project-icon">✅</div>
                                <div class="project-title">Sudah Selesai</div>
                                <div class="project-number text-success">
                                    {{ $completedBuild }}
                                </div>

                            </div>

                            <a href="{{ route('projects.index',['type'=>1]) }}"
                            class="project-card text-decoration-none text-dark">

                                <div class="project-icon">🎨</div>
                                <div class="project-title">Desain</div>
                                <div class="project-number text-info">
                                    {{ $totalDesign }}
                                </div>

                            </a>
                            <a href="{{ route('projects.index',['type'=>2]) }}"
                            class="project-card text-decoration-none text-dark">

                                <div class="project-icon">📑</div>
                                <div class="project-title">RAB</div>
                                <div class="project-number text-warning">
                                    {{ $totalRab }}
                                </div>

                            </a>

                            <a href="{{ route('projects.index',['type'=>3]) }}"
                            class="project-card text-decoration-none text-dark">

                                <div class="project-icon">🏗</div>
                                <div class="project-title">Build</div>
                                <div class="project-number text-success">
                                    {{ $totalBuild }}
                                </div>

                            </a>

                        </div>
                        <hr>

                        <h6 class="mb-3">
                            🏗 Progress Tertinggi
                        </h6>

                        @foreach($topBuildProjects as $project)

                            <div class="mb-3">

                                <div class="d-flex justify-content-between">

                                    <span>
                                        {{ $project->project_name }}
                                    </span>

                                    <span>
                                        {{ number_format($project->progress,0) }}%
                                    </span>

                                </div>

                                <div class="progress mt-1" style="height:8px;">
                                    <div
                                        class="progress-bar"
                                        style="width: {{ $project->progress }}%">
                                    </div>
                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>
            </div>
            @endcan --}}
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.querySelector('.alert-wrapper');
    if (!wrapper) return;

    const alerts = document.querySelectorAll('.alert-item');
    const total = alerts.length;
    let currentIndex = 0;

    function updateSlide() {
        const offset = -currentIndex * 100;
        wrapper.style.transform = `translateX(${offset}%)`;
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % total;
        updateSlide();
    }

    const nextBtn = document.getElementById('nextAlert');
    if (nextBtn) {
        nextBtn.addEventListener('click', nextSlide);
    }

    updateSlide();
});
</script>
<script>

function updateClock() {

    const now = new Date();

    document.getElementById('clock').innerHTML =
        now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        }) + ' WIB';
}

updateClock();
setInterval(updateClock, 1000);

let stream;

function initCamera(config) {

    const modal = document.getElementById(config.modal);
    if (!modal) return;

    const camera = document.getElementById(config.camera);
    const canvas = document.getElementById(config.canvas);
    const preview = document.getElementById(config.preview);

    const capture = document.getElementById(config.capture);
    const retake = document.getElementById(config.retake);
    const confirm = document.getElementById(config.confirm);

    const photo = document.getElementById(config.photo);

    const lat = document.getElementById(config.lat);
    const lng = document.getElementById(config.lng);

    modal.addEventListener('shown.bs.modal', async () => {

        if (!navigator.mediaDevices?.getUserMedia) {
            alert("Browser tidak mendukung Camera API.");
            return;
        }

        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: "user"
            }
        });

        camera.srcObject = stream;

        if (navigator.geolocation) {

            navigator.geolocation.getCurrentPosition(

                (position) => {

                    lat.value = position.coords.latitude;
                    lng.value = position.coords.longitude;

                },

                () => {

                    alert("Tidak bisa mendapatkan lokasi.");

                },

                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }

            );

        }

    });

    modal.addEventListener('hidden.bs.modal', () => {

        if (stream) {

            stream.getTracks().forEach(track => track.stop());

        }

        camera.classList.remove('d-none');
        preview.classList.add('d-none');

        capture.classList.remove('d-none');
        retake.classList.add('d-none');
        confirm.classList.add('d-none');

        photo.value = '';

    });

    capture.addEventListener('click', () => {

        canvas.width = camera.videoWidth;
        canvas.height = camera.videoHeight;

        canvas.getContext('2d').drawImage(camera, 0, 0);

        const image = canvas.toDataURL('image/jpeg');

        photo.value = image;

        preview.src = image;

        preview.classList.remove('d-none');
        camera.classList.add('d-none');

        capture.classList.add('d-none');
        retake.classList.remove('d-none');
        confirm.classList.remove('d-none');

    });

    retake.addEventListener('click', () => {

        photo.value = '';

        preview.classList.add('d-none');
        camera.classList.remove('d-none');

        capture.classList.remove('d-none');
        retake.classList.add('d-none');
        confirm.classList.add('d-none');

    });

}
initCamera({

    modal: 'checkInModal',

    camera: 'checkInCamera',
    canvas: 'checkInCanvas',
    preview: 'checkInPreview',

    capture: 'checkInCapture',
    retake: 'checkInRetake',
    confirm: 'checkInConfirm',

    photo: 'checkInPhoto',

    lat: 'checkInLat',
    lng: 'checkInLng'

});
initCamera({

    modal: 'checkOutModal',

    camera: 'checkOutCamera',
    canvas: 'checkOutCanvas',
    preview: 'checkOutPreview',

    capture: 'checkOutCapture',
    retake: 'checkOutRetake',
    confirm: 'checkOutConfirm',

    photo: 'checkOutPhoto',

    lat: 'checkOutLat',
    lng: 'checkOutLng'

});
initCamera({

    modal: 'startOvertimeModal',

    camera: 'startOvertimeCamera',
    canvas: 'startOvertimeCanvas',
    preview: 'startOvertimePreview',

    capture: 'startOvertimeCapture',
    retake: 'startOvertimeRetake',
    confirm: 'startOvertimeConfirm',

    photo: 'startOvertimePhoto',

    lat: 'startOvertimeLat',
    lng: 'startOvertimeLng'

});
initCamera({

    modal: 'finishOvertimeModal',

    camera: 'finishOvertimeCamera',
    canvas: 'finishOvertimeCanvas',
    preview: 'finishOvertimePreview',

    capture: 'finishOvertimeCapture',
    retake: 'finishOvertimeRetake',
    confirm: 'finishOvertimeConfirm',

    photo: 'finishOvertimePhoto',

    lat: 'finishOvertimeLat',
    lng: 'finishOvertimeLng'

});
</script>
@endpush
<style>
.footer.footer-transparent {
    display: flex;
    margin-left: 0;
    flex-direction: column;
    padding: 20px 20px 20px 16px;
    transition: all .3s ease;
}

.sidebar-collapsed .footer.footer-transparent {
    padding-left: 20px;
    padding-right: 18px;
    margin-left: 0 !important;
    margin-right: 0 !important;
}
</style>