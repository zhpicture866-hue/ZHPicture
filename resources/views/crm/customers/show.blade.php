@extends('tablar::page')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col d-flex align-items-center">
                <a href="{{ route('customers.index') }}" class="btn btn-dark d-flex align-items-center me-3">
                    <i class="ti ti-arrow-left"></i>
                </a>
                <div>
                    <h2 class="page-title mb-0">{{ $customer->display_name_with_title ?: $customer->nic }}</h2>
                    <div class="text-secondary">{{ $customer->nic }}, {{ $customer->readable_loyalty_level }}</div>
                </div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <a href="{{ route('crm.interaksi.index') }}" class="btn btn-outline-dark">Interaksi</a>
                    <a href="{{ route('crm.reaktivasi.index') }}" class="btn btn-outline-dark">Follow-up Hari Ini</a>
                    @if ($customer->whatsapp)
                        <a href="https://wa.me/{{ $customer->whatsapp }}" target="_blank" rel="noopener" class="btn btn-dark">
                            <i class="ti ti-brand-whatsapp me-1"></i> Buka WhatsApp
                        </a>
                    @endif
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

        <div class="row row-cards">
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">Ringkasan Customer</h3></div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-6 text-secondary fw-normal">WhatsApp</dt>
                            <dd class="col-6 text-end">{{ $customer->whatsapp ?: '-' }}</dd>
                            <dt class="col-6 text-secondary fw-normal">Email</dt>
                            <dd class="col-6 text-end">{{ $customer->email ?: '-' }}</dd>
                            <dt class="col-6 text-secondary fw-normal">Loyalty</dt>
                            <dd class="col-6 text-end">{{ $customer->readable_loyalty_level }}</dd>
                            <dt class="col-6 text-secondary fw-normal">Jumlah project</dt>
                            <dd class="col-6 text-end">{{ $jumlahProject }}</dd>
                            <dt class="col-6 text-secondary fw-normal">Project terakhir</dt>
                            <dd class="col-6 text-end">{{ $projectTerakhir ? $projectTerakhir->format('d/m/Y') : '-' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Profil CRM</h3></div>
                    <div class="card-body">
                        @php($profil = $customer->crmProfil)
                        <form method="POST" action="{{ route('crm.customers.profil', $customer) }}">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label">Sumber lead</label>
                                <select name="sumber" class="form-select">
                                    <option value="">-- Pilih --</option>
                                    @foreach (config('crm.sumber') as $k => $l)
                                        <option value="{{ $k }}" @selected(old('sumber', $profil?->sumber) === $k)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Merk HP</label>
                                    <input type="text" name="merk_hp" class="form-control" value="{{ old('merk_hp', $profil?->merk_hp) }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Tipe HP</label>
                                    <input type="text" name="tipe_hp" class="form-control" value="{{ old('tipe_hp', $profil?->tipe_hp) }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profesi</label>
                                <input type="text" name="profesi" class="form-control" value="{{ old('profesi', $profil?->profesi) }}">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Akun sosial media</label>
                                <input type="text" name="akun_sosmed" class="form-control" placeholder="@username" value="{{ old('akun_sosmed', $profil?->akun_sosmed) }}">
                            </div>
                            @can('kelola data crm')
                                <button class="btn btn-dark w-100">Simpan profil CRM</button>
                            @endcan
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                @can('kelola data crm')
                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">Catat Follow-up</h3></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('crm.followups.store', $customer) }}" class="row g-3">
                            @csrf
                            <div class="col-md-4">
                                <label class="form-label required">Kanal</label>
                                <select name="kanal" class="form-select">
                                    @foreach (config('crm.kanal') as $k => $l)
                                        <option value="{{ $k }}" @selected(old('kanal') === $k)>{{ $l }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Follow-up berikutnya</label>
                                <input type="date" name="jadwal_berikutnya" class="form-control" value="{{ old('jadwal_berikutnya') }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label required">Catatan</label>
                                <textarea name="catatan" rows="3" class="form-control" placeholder="Ringkasan percakapan" required>{{ old('catatan') }}</textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-dark">Simpan follow-up</button>
                            </div>
                        </form>
                    </div>
                </div>
                @endcan

                <div class="card mb-3">
                    <div class="card-header"><h3 class="card-title">Riwayat Follow-up</h3></div>
                    <div class="list-group list-group-flush">
                        @forelse ($followups as $f)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ config('crm.kanal')[$f->kanal] ?? $f->kanal }}</strong>
                                    <span class="text-secondary">{{ $f->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="mt-1">{{ $f->catatan }}</div>
                                @if ($f->jadwal_berikutnya)
                                    <div class="mt-2 d-flex align-items-center gap-2 text-secondary">
                                        Berikutnya: {{ $f->jadwal_berikutnya->format('d/m/Y') }}
                                        <span class="badge {{ $f->status === 'selesai' ? 'bg-green-lt' : 'bg-yellow-lt' }}">{{ $f->status }}</span>
                                        @if ($f->status !== 'selesai')
                                            @can('kelola data crm')
                                            <form method="POST" action="{{ route('crm.followups.selesai', $f) }}" class="ms-2">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-dark">Tandai selesai</button>
                                            </form>
                                            @endcan
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="list-group-item text-secondary">Belum ada follow-up untuk customer ini.</div>
                        @endforelse
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">Jadwal Reaktivasi</h3></div>
                    <div class="list-group list-group-flush">
                        @forelse ($jadwal as $j)
                            <div class="list-group-item d-flex justify-content-between">
                                <span>{{ $j->interval }}, {{ $j->tanggal_jadwal->format('d/m/Y') }}</span>
                                <span class="badge {{ $j->status === 'selesai' ? 'bg-green-lt' : 'bg-yellow-lt' }}">{{ $j->status }}</span>
                            </div>
                        @empty
                            <div class="list-group-item text-secondary">Belum ada jadwal. Jadwal dibuat otomatis dari tanggal project terakhir.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
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
