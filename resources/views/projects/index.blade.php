@extends('tablar::page')

@section('content')
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                
                <!-- Page title actions -->
                <div class="col-12 col-md-auto ms-auto d-print-none">
                    <div class="btn-list">
                @can('tambah data proyek')       
                        <a href="{{ route("projects.create") }}" class="btn btn-dark" >
                            <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24"
                                 viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <line x1="12" y1="5" x2="12" y2="19"/>
                                <line x1="5" y1="12" x2="19" y2="12"/>
                            </svg>
                            Tambah Data Proyek
                        </a>
                 @endcan
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center mb-4">Daftar Proyek</h2>
                    </div>

                    <div class="card-body">
                        <div class="mb-3">
                            <input type="search" id="project-search" class="form-control"
                                placeholder="Cari proyek, customer, karyawan...">
                        </div>

                        <div id="project-list" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3">
                            @include('projects._cards')
                        </div>

                        <div id="project-pagination" class="mt-3 d-flex justify-content-center">
                            {!! $projects->links('pagination::bootstrap-5') !!}
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
@can('tambah data proyek')
<a href="{{ route('projects.create') }}"
   class="mobile-fab d-md-none">

    <svg xmlns="http://www.w3.org/2000/svg"
         width="26"
         height="26"
         viewBox="0 0 24 24"
         stroke-width="2"
         stroke="currentColor"
         fill="none"
         stroke-linecap="round"
         stroke-linejoin="round">

        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
        <line x1="12" y1="5" x2="12" y2="19"/>
        <line x1="5" y1="12" x2="19" y2="12"/>

    </svg>

</a>
@endcan
@endsection

@push('js')
    <script>
        let searchTimer;
        let currentUrl = "{{ route('projects.index') }}";

        function loadProjects(url = currentUrl, search = $('#project-search').val()) {
            currentUrl = url;

            $('#project-list').css('opacity', .5);

            $.get(url, { search: search })
                .done(res => {
                    $('#project-list').html(res.html);
                    $('#project-pagination').html(res.pagination);
                })
                .always(() => $('#project-list').css('opacity', 1));
        }

        // Cari (debounce 400ms), kembali ke halaman 1
        $('#project-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(
                () => loadProjects("{{ route('projects.index') }}", this.value),
                400
            );
        });

        // Klik pagination tanpa reload halaman
        $(document).on('click', '#project-pagination .pagination a', function (e) {
            e.preventDefault();
            loadProjects(this.href);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        $(document).on('click', '.delete-projects', function () {
            const projectId = $(this).data('id');

            Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data akan hilang secara permanen.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'

            }).then((result) => {

                if (result.isConfirmed) {
                    $.ajax({

                        url: `/projects/${projectId}`,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}',
                        },

                        success: function (response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: 'Data Proyek telah dihapus.',
                                    timer: 2000,
                                    showConfirmButton: false
                            });

                        loadProjects();
                        } else {

                            Swal.fire('Gagal', response.message || 'Tidak bisa menghapus data.', 'error');
                        }
                        },

                    error: function () {

                    Swal.fire('Error', 'Terjadi kesalahan saat menghapus.', 'error');
                    }

                    });
                }
            });
        });
    </script>

    @if (session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Sukses!',
            text: @json(session('success')),
            timer: 2000,
            showConfirmButton: false
        });
    </script>
    @endif
@endpush