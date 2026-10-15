@extends('tablar::page')
@section('content')
<div class="container-xl" style="padding-top:80px">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="ti ti-shield-lock me-2"></i> Edit Role: <strong>{{ $role->name }}</strong>
            </h5>
            <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Kembali
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('roles.update', $role->id) }}" method="POST">
                @csrf
                @method('PUT')
                @if ($errors->any())
                    <div class="alert alert-danger">{{ implode(', ', $errors->all()) }}</div>
                @endif
                <div class="row g-4">
                    <div class="col-md">
                        <label for="roleName" class="form-label fw-bold text-secondary">Nama Role</label>
                        <input type="text" id="roleName" name="name" value="{{ $role->name }}" 
                            class="form-control form-control-md shadow-sm" placeholder="Masukkan nama role">
                    </div>
                    <div class="col-md">
                        <label class="form-label fw-bold text-secondary">Group Role</label>

                        <select name="role_group" class="form-select select2" required>
                            <option value="">-- Pilih Group --</option>
                            <option value="Internal" {{ old('role_group', $role->role_group) == "Internal" ? 'selected' : '' }}>Internal</option>
                            <option value="Eksternal" {{ old('role_group', $role->role_group) == "Eksternal" ? 'selected' : '' }}>Eksternal</option>
                        </select>

                        @error('role_group')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold text-dark mb-3">
                    <i class="ti ti-lock me-2"></i> Permissions Berdasarkan Modul
                </h5>

                @foreach ($groupedPermissions as $moduleName => $modulePermissions)
                    <div class="card mb-3 border-0 shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-dark">
                                <i class="ti ti-folder me-1 text-primary"></i>
                                {{ $moduleName ?? 'Tanpa Modul' }}
                            </span>
                            <button type="button" 
                                class="btn btn-sm btn-outline-secondary btn-select-module" 
                                data-module="{{ \Illuminate\Support\Str::slug($moduleName) }}">
                                Pilih Semua
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($modulePermissions as $perm)
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input permission-checkbox module-{{ \Illuminate\Support\Str::slug($moduleName) }}" 
                                                   type="checkbox" 
                                                   name="permissions[]" 
                                                   id="perm-{{ $perm->id }}" 
                                                   value="{{ $perm->name }}"
                                                   {{ $role->hasPermissionTo($perm->name) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm-{{ $perm->id }}">
                                                {{ ucfirst($perm->name) }}
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-dark btn-md px-4">
                        <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: "-- Pilih --",
        width: '100%'
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-select-module').forEach(btn => {
        btn.addEventListener('click', function() {
            const moduleSlug = this.dataset.module;
            const checkboxes = document.querySelectorAll(`.module-${moduleSlug}`);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
            this.textContent = allChecked ? 'Pilih Semua' : 'Batalkan Semua';
        });
    });
});
</script>
@endpush
