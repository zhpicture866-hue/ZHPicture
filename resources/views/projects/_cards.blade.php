@forelse ($projects as $project)
    @php
        $current = $project->levels->where('is_completed', false)->sortBy('level_order')->first();
    @endphp

    <div class="col">
        <div class="card project-card h-100">
            <div class="card-body d-flex flex-column">

                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <a href="{{ route('projects.continue', $project->id) }}"
                       class="project-title fw-bold text-decoration-none">
                        {{ Str::title($project->project_name ?? '-') }}
                    </a>
                    <span class="badge bg-info flex-shrink-0">
                        {{ $project->projectType?->name ?? '-' }}
                    </span>
                </div>

                <dl class="project-meta mb-3">
                    <div><dt>Customer</dt><dd>{{ $project->customer?->user?->fullname ?? '-' }}</dd></div>
                    <div><dt>Karyawan</dt><dd>{{ $project->employee?->user?->fullname ?? '-' }}</dd></div>
                    <div><dt>Affiliator</dt><dd>{{ $project->affiliator?->user?->fullname ?? '-' }}</dd></div>
                    <div>
                        <dt>Mulai</dt>
                        <dd>{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d/m/Y') : '-' }}</dd>
                    </div>
                </dl>

                <div class="mt-auto d-flex justify-content-between align-items-center">
                    @if ($current)
                        <a href="{{ route('projects.continue', $project->id) }}" class="badge bg-primary">
                            {{ $current->level_name }}
                        </a>
                    @else
                        <span class="badge bg-success">Selesai</span>
                    @endif

                    @can('hapus data proyek')
                        <button type="button" data-id="{{ $project->id }}"
                                class="btn btn-icon btn-sm btn-dark delete-projects">
                            <i class="ti ti-trash"></i>
                        </button>
                    @endcan
                </div>

            </div>
        </div>
    </div>
@empty
    <div class="col w-100" style="flex: 0 0 100%; max-width: 100%;">
        <div class="text-center text-muted py-5">Belum ada proyek.</div>
    </div>
@endforelse