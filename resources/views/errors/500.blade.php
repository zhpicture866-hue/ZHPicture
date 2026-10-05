<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>500 - Server Error</title>

    @vite(['resources/css/app.css'])
</head>

<body class="d-flex align-items-center justify-content-center min-vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">

                <div class="card shadow-sm border-0">
                    <div class="card-body text-center p-5">

                        <div class="mb-4">
                            <i class="ti ti-alert-triangle"
                               style="font-size: 70px;"></i>
                        </div>

                        <h1 class="display-4 fw-bold">
                            500
                        </h1>

                        <h2 class="mb-3">
                            Terjadi Kesalahan
                        </h2>

                        <p class="text-muted mb-4">
                            Maaf, terjadi kesalahan pada server.
                            Silakan coba kembali beberapa saat lagi.
                        </p>


                        {{-- DETAIL ERROR KHUSUS SUPER ADMIN --}}
                        @auth
                            @if (auth()->user()->hasRole('Super-Admin'))

                                <div class="alert alert-danger text-start mt-4">

                                    <div class="fw-bold mb-3">
                                        <i class="ti ti-bug"></i>
                                        Detail Error
                                    </div>

                                    @if ($exception && $exception->getMessage())

                                        <div class="mb-3">
                                            <div class="text-muted small mb-1">
                                                Message
                                            </div>

                                            <pre class="mb-0"
                                                 style="white-space: pre-wrap; word-break: break-word;">{{ $exception->getMessage() }}</pre>
                                        </div>

                                    @endif

                                    @if ($exception && $exception->getFile())

                                        <div class="mb-3">
                                            <div class="text-muted small mb-1">
                                                File
                                            </div>

                                            <code>
                                                {{ $exception->getFile() }}
                                            </code>
                                        </div>

                                    @endif

                                    @if ($exception && $exception->getLine())

                                        <div>
                                            <div class="text-muted small mb-1">
                                                Line
                                            </div>

                                            <code>
                                                {{ $exception->getLine() }}
                                            </code>
                                        </div>

                                    @endif

                                </div>

                            @endif
                        @endauth


                        <a href="{{ url('/') }}"
                           class="btn btn-primary">

                            <i class="ti ti-home"></i>
                            Kembali ke Beranda

                        </a>

                        <button type="button"
                                onclick="location.reload()"
                                class="btn btn-outline-secondary">

                            <i class="ti ti-refresh"></i>
                            Coba Lagi

                        </button>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>