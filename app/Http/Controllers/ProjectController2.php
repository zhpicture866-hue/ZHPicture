<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Affiliator;
use App\Models\Worker;
use App\Models\Invoice;
use App\Models\InvoiceBuild;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\SubDistrict;
use App\Models\PostalCode;
use App\Models\Project;
use App\Models\ProjectLevel;
use App\Models\ProjectTask;
use App\Models\JobCategory;
use App\Models\RabProcess;
use App\Models\RabProcessCategory;
use App\Models\BuildDailyReport;
use App\Models\BuildProcessItem;
use App\Models\BuildPlans;
use App\Models\RabProcessItem;
use App\Models\WeeklyReport;
use App\Models\User;
use App\Models\TechnicalJustification;
use App\Services\ProjectNotifier;
use App\Services\BuildPlanSyncService;
use App\Services\BuildProcessSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use DB;

class ProjectController extends Controller
{
       public function index(Request $request)
{
    $auth = auth()->user();

    $query = Project::with([
        'customer.user:id,fullname',
        'employee.user:id,fullname',
        'affiliator.user:id,fullname',
        'province:id,name',
        'city:id,name',
        'district:id,name',
        'subDistrict:id,name',
        'postalCode:id,postal_code',
        'levels:id,project_id,level_order,level_name,is_completed'
    ]);

    // Jika ada hak akses untuk membatasi data
if (
    $auth->can('lihat data proyek') &&
    !$auth->can('lihat daftar proyek')
) {
    $query->where(function ($q) use ($auth) {
        $q->whereHas('customer', function ($qq) use ($auth) {
            $qq->where('user_id', $auth->id);
        })
        ->orWhereHas('employee', function ($qq) use ($auth) {
            $qq->where('user_id', $auth->id);
        });
    });
}

if ($request->filled('type')) {
    $query->where('project_type', (int) $request->type);
}

    if ($request->ajax()) {
        $projects = $query->get();

        $statusLabel = [
            1 => 'Proses',
            2 => 'Revisi',
            3 => 'Butuh Persetujuan',
            4 => 'Selesai'
        ];

        return DataTables::of($query)
        ->addIndexColumn()

        ->addColumn('province_name', fn($row) => $row->province->name ?? '-')
        ->addColumn('city_name', fn($row) => $row->city->name ?? '-')
        ->addColumn('district_name', fn($row) => $row->district->name ?? '-')
        ->addColumn('sub_district_name', fn($row) => $row->subDistrict->name ?? '-')
        ->addColumn('postal_code', fn($row) => $row->postalCode->postal_code ?? '-')
        ->addColumn('customer', fn($row) => $row->customer?->user?->fullname ?? '-')
        ->addColumn('employee', fn($row) => $row->employee?->user?->fullname ?? '-')
        ->addColumn('affiliator', fn($row) => $row->affiliator?->user?->fullname ?? '-')
        ->addColumn('project_type', fn($row) => $this->readableProjectType($row->project_type))
        ->addColumn('start_date', fn($row) => $row->start_date ? Carbon::parse($row->start_date)->format('d/m/Y') : '-')

        ->addColumn('project_status', function ($row) use ($statusLabel) {

            $label = $statusLabel[$row->project_status] ?? 'Tidak Diketahui';

            $color = match ($row->project_status) {
                1 => 'info',
                2 => 'danger',
                3 => 'warning',
                4 => 'success',
                default => 'secondary'
            };

            return '<span class="badge bg-' . $color . '">' . $label . '</span>';
        })

        ->addColumn('current_level', function ($row) {
            $current = $row->levels
                ->where('is_completed', false)
                ->sortBy('level_order')
                ->first();

            // Jika semua selesai
            if (!$current) {
                return '<span class="badge bg-success">Selesai</span>';
            }

            $url = route('projects.continue', $row->id);

            return '<a href="'.$url.'" class="badge bg-primary" style="cursor:pointer;">
                        '.$current->level_name.'
                    </a>';
        })
        ->editColumn('project_name', function ($row) {
                    $url = route('projects.continue', $row->id);
                    $name = Str::title($row->project_name ?? '-');
                    return '<a href="'.$url.'">'.e($name).'</a>';
                })

        // Tombol Aksi
        ->addColumn('action', function ($project) {
            $buttons = '';
            if (auth()->user()->can('hapus data proyek')) {
                $buttons .= '<button data-id="' . $project->id . '" 
                            class="btn btn-icon btn-sm btn-dark delete-projects">
                            <i class="ti ti-trash"></i></button>';
            }
            return $buttons;
        })

        ->rawColumns(['current_level', 'action', 'project_status', 'project_name'])
        ->make(true);
    }

    return view('projects.index');
}

    private function readableProjectType($value)
    {
        return match ((int) $value) {
            1 => 'Desain',
            2 => 'RAB',
            3 => 'Build',
            default => '-',
        };
    }

public function create(Request $request)
{
    $project = null;
    if ($request->has('project_id')) {
        $project = $this->loadBaseProject($request->project_id);
    }
    $activeStep  = $this->getCurrentStep($project);
    $projectType = $project?->project_type;
    if ($project) {
        $extra = $this->resolveExtraRelations($activeStep, $projectType);
        if (!empty($extra)) {
            $project->load($extra);
        }
    }
    $canEdit = auth()->user()->can('lihat daftar proyek');
    $justekId = request('justek_id');

    // List semua justek milik project ini (untuk tabel Riwayat)
    $technicalJustifications = $project
        ? TechnicalJustification::where('project_id', $project->id)->get()
        : collect();

    // Detail justek yang lagi dibuka (untuk tabel Detail)
    $technicalJustification = $justekId
        ? $technicalJustifications->firstWhere('id', (int) $justekId)
        : null;

    // Kalau item-nya belum ke-load di collection di atas, load relasi 'items'-nya
    if ($technicalJustification) {
        $technicalJustification->load('items');
    }
    $defaults = [
        'surveyInvoice'  => null,
        'surveyApproved' => false,
        'surveyWaiting'  => false,
        'surveyRejected' => false,
        'isFreeSurvey'   => false,
        'invoiceDp'      => null,
        'invoiceRab'     => null,
        'invoiceBuild'   => null,
        'weeks'          => 0,
        'usedDates'      => [],
        'nextDate'       => now(),
        'reports'        => collect(),
        'buildItems'     => collect(),
        'groupedItems'   => collect(),
        'buildPlans'     => collect(),
        'groupedPlans'   => collect(),
        'rabItems'       => collect(),
    ];
    $viewData = array_merge($defaults, compact(
        'project', 'activeStep', 'canEdit', 'technicalJustification', 'technicalJustifications'
    ));

    if ($activeStep >= 3 && $project) {
        $surveyData = $this->resolveSurveyData($project, $activeStep);
        $viewData   = array_merge($viewData, $surveyData);
        // Mungkin activeStep berubah jadi 4
        $activeStep = $viewData['activeStep'];
    }

    $viewData['timelineSteps'] = $this->buildTimelineSteps($project, $activeStep);

    if ($activeStep >= 6 && $project) {
        $viewData = array_merge($viewData, $this->resolveInvoiceData($project));
    }

    if ($projectType == 3 && $activeStep >= 8 && $project) {
        $viewData = array_merge($viewData, $this->resolveBuildData($project));
    }

    if ($projectType == 3 && $activeStep >= 8 && $project) {
        $viewData = array_merge($viewData, $this->resolveBuildPlanData($project));
    }
    return view('projects.create', array_merge(
        $this->formData($project, $activeStep, $projectType),
        $viewData
    ));
}
private function loadBaseProject($projectId)
{
    return Project::with([
        'customer.user',
        'employee',
        'levels.employees',
        'planning',
        'invoices',                           
        'rab:id,project_id,job_duration',     
    ])->findOrFail($projectId);
}
private function resolveExtraRelations(int $activeStep, ?int $projectType): array
{
    $relations = [];

    if ($activeStep >= 2) {
        $relations[] = 'consultation.items';
    }

    if ($activeStep >= 4) {
        $relations[] = 'survey.items';
    }

    if ($activeStep >= 5) {
        $relations[] = 'offer.items';

        if ($projectType == 2) {
            $relations[] = 'offer.rab.items.category';
        }
    }

    if ($projectType == 2 && $activeStep >= 7) {
        $relations[] = 'rab.items';
    }

    if ($projectType == 3 && $activeStep >= 8) {
        $relations = array_merge($relations, [
            'buildItems.jobCategory',
            'buildItems.weeklyProgresses',
            'buildItems.tambahan.weeklyProgresses',
            'dailyReports.works.rabProcessItem',
            'dailyReports.workers.worker.user',
            'dailyReports.materials',
        ]);
    }

    return $relations;
}
private function resolveSurveyData($project, int $activeStep): array
{
    $surveyInvoice = $project->invoices
        ->where('invoice_type', 'survey')
        ->sortByDesc('created_at')
        ->first();

    $surveyApproved = $surveyInvoice?->status === 'approved';
    $surveyWaiting  = $surveyInvoice?->status === 'waiting_approval';
    $surveyRejected = $surveyInvoice?->status === 'rejected';
    $isFreeSurvey   = !$surveyInvoice && $project->levels->firstWhere('level_order', 3)?->is_started;

    if (
        $project->planning
        && ($isFreeSurvey || $surveyApproved)
        && $activeStep == 3
    ) {
        $activeStep = 4;
    }

    return compact(
        'surveyInvoice', 'surveyApproved', 'surveyWaiting',
        'surveyRejected', 'isFreeSurvey', 'activeStep'
    );
}
private function resolveInvoiceData($project): array
{
    $invoiceDp = $project->invoices
        ->where('invoice_type', Invoice::TYPE_DP)
        ->first();

    $invoiceRab = $project->invoices
        ->where('invoice_type', Invoice::TYPE_RAB)
        ->first();

    $invoiceBuild = InvoiceBuild::where('project_id', $project->id)
        ->where('invoice_type', InvoiceBuild::TYPE_BUILD)
        ->first();

    return compact('invoiceDp', 'invoiceRab', 'invoiceBuild');
}
private function resolveBuildData($project): array
{
    $weeks = $project->rab?->job_duration ?? 0;

    $usedDates = $project->dailyReports
        ->pluck('tanggal')
        ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'))
        ->toArray();

    $nextDate = Carbon::parse($project->start_date);

    while (
        in_array($nextDate->format('Y-m-d'), $usedDates)
        && $nextDate->lte($project->end_date)
    ) {
        $nextDate->addDay();
    }

    $reports = $project->dailyReports->sortBy('tanggal')->groupBy('minggu');

    $buildItems = $project->buildItems;

    $buildItems->each(function ($item) {
        $item->progress_map = $item->weeklyProgresses
            ->keyBy('week_no');

        if ($item->relationLoaded('tambahan')) {
            $item->tambahan->each(function ($sub) {

                $sub->progress_map = $sub->weeklyProgresses
                    ->keyBy('week_no');
            });
        }
    });
    $groupedItems = $buildItems
    ->sortBy('order_no')          // urutkan dulu berdasarkan order_no murni
    ->groupBy(function ($item) {
        return $item->floor_name ?: 'Tanpa Lantai';
    })                             // urutan floor mengikuti order_no terkecil di tiap floor
    ->map(function ($floorItems, $floorName) {

        return [
            'floor_name' => $floorName,

            'categories' => $floorItems
                ->groupBy(function ($item) {
                    return $item->category_name ?: 'Tanpa Kategori';
                })
                ->map(function ($items, $categoryName) {

                    return [
                        'category_name' => $categoryName,

                        'items' => $items
                            ->sortBy('order_no')
                            ->values(),
                    ];
                })
                ->values(),
        ];
    })
    ->values();
    $rabItems = $groupedItems
    ->flatMap(function ($floor) {
        return collect($floor['categories'])
            ->flatMap(function ($category) {
                return $category['items'];
            });
    })
    ->values();
    $weeklyReports = WeeklyReport::where('project_id', $project->id)
        ->get()
        ->keyBy('minggu');

    return compact(
        'weeks',
        'usedDates',
        'nextDate',
        'reports',
        'buildItems',
        'groupedItems',
        'weeklyReports',
        'rabItems'
    );
}

    public function store(ProjectRequest $request)
{
    abort_if(auth()->user()->cannot('lihat daftar proyek'), 403);

    $project = DB::transaction(function () use ($request) {

        $project = Project::create($request->validated());

        $project->generateLevels();

        return $project;
    });

    $project->load(['employee.user', 'customer.user']);

    $event = 'project_created';
    $cfg   = config("project_events.project_created");

    if (!$cfg) {
        throw new \Exception("Config project_events.$event not found");
    }

    ProjectNotifier::notifyUsers(
        [auth()->user()],
        ProjectNotifier::makePayload($project, [
            'type'    => $event,
            'role'    => 'created_self',
            'title'   => $cfg['title'],
            'message' => $cfg['message']['created_self'],
            'url'     => route('projects.create', ['project_id' => $project->id]),
        ])
    );

    if ($project->employee?->user && $project->employee->user->id !== auth()->id()) {
        ProjectNotifier::notifyUsers(
            [$project->employee->user],
            ProjectNotifier::makePayload($project, [
                'type'    => $event,
                'role'    => 'assigned',
                'title'   => $cfg['title'],
                'message' => $cfg['message']['assigned'],
                'url'     => route('projects.create', ['project_id' => $project->id]),
            ])
        );
    }

    $directors = User::role('Tim')->get();

    ProjectNotifier::notifyUsers(
        $directors,
        ProjectNotifier::makePayload($project, [
            'type'    => $event,
            'role'    => 'director',
            'title'   => $cfg['title'],
            'message' => $cfg['message']['director'],
            'url'     => route('projects.create', ['project_id' => $project->id]),
        ]),
        exceptUserId: auth()->id()
    );

    if ($project->customer?->user) {
        ProjectNotifier::notifyUsers(
            [$project->customer->user],
            ProjectNotifier::makePayload($project, [
                'type'    => $event,
                'role'    => 'customer',
                'title'   => $cfg['title'],
                'message' => $cfg['message']['customer'],
                'url'     => route('projects.create', ['project_id' => $project->id]),
            ])
        );
    }

        return redirect()
            ->route('projects.create', ['project_id' => $project->id])
            ->with('success', 'Project berhasil dibuat.');
}

    private function getCurrentStep($project)
    {
        if (!$project) return 1;

        $current = $project->levels
            ->where('is_completed', false)
            ->sortBy('level_order')
            ->first();

        return $current ? $current->level_order + 1 : 9;
    }

    public function continue(Project $project, Request $request)
{
    $project->load([
        'customer.user',
        'employee',
        'levels.employees',
        'offer.items',
        'offer.rab.items',
        'rab.items',
        'buildItems.jobCategory',
        'buildItems.weeklyProgresses', 
        'buildItems.tambahan.weeklyProgresses',
        'dailyReports.works.rabProcessItem',
        'dailyReports.workers.worker.user',
        'dailyReports.materials'
    ]);

    $activeStep = $this->computeActiveStep($project);

    return redirect()->route('projects.create', [
        'project_id' => $project->id,
        'step'       => $activeStep
    ]);
}

private function computeActiveStep($project, $request = null)
{
    if ($request && $request->filled('step')) {
        return (int) $request->step;
    }

    if (!$project) {
        return 1;
    }

    $current = $project->levels
        ->where('is_completed', false)
        ->sortBy('level_order')
        ->first();

    return $current ? $current->level_order + 1 : 9;
}

private function stepKeyMap()
{
    return [
        0 => 'project',
        1 => 'form-konsultasi',
        2 => 'detail-konsultasi',
        3 => 'planning',
        4 => 'survei',
        5 => 'offer',
        6 => 'kontrak',
        7 => 'invoice',
        8 => 'work',
        9 => 'invoice-final',
        10 => 'final',
    ];
}

public function update(Request $request, Project $project)
{
    abort_if(auth()->user()->cannot('lihat daftar proyek'), 403);
    
    $project->update($request->all());

    return back()->with('success', 'Data proyek berhasil diperbarui!');
}
public function show(Project $project)
{
    return redirect()->route('projects.create', ['project_id' => $project->id]);
}
     public function destroy(Project $project) 
    {
        if ($project) {
            $project->delete();
            return response()->json(['status' => 'success', 'message' => 'Project deleted successfully']);
        }

        return response()->json(['status' => 'failed', 'message' => 'Unable to delete']);
    }
private function buildTimelineSteps($project, int $activeStep): \Illuminate\Support\Collection
{
    if (!$project) {
        return collect([]);
    }
    $map = $this->stepKeyMap();
    return $project->levels
        ->sortBy('level_order')
        ->map(function ($level) use ($activeStep, $map) {
            $order = $level->level_order + 1;
            return [
                'id'        => $map[$order] ?? 'step-' . $order,
                'label'     => $level->level_name,
                'completed' => $level->is_completed,
                'current'   => $activeStep === $order,
            ];
        })
        ->values();
}
private function formData($project = null, int $activeStep = 1, ?int $projectType = null, array $merge = []): array
{
    $data = [
        'projectStatus' => [
            1 => 'Proses',
            2 => 'Revisi',
            3 => 'Butuh Persetujuan',
            4 => 'Selesai',
        ],
    ];

    if ($activeStep >= 1) {
        $data['employees']   = Employee::with('user:id,fullname')->get(['id', 'user_id']);
        $data['customers']   = Customer::with('user:id,fullname')->get(['id', 'user_id']);
        $data['affiliators'] = Affiliator::with('user:id,fullname')->get(['id', 'user_id']);
        $data['provinces']   = Province::all();
    }

    if ($projectType == 3 && $activeStep >= 8) {
        $data['workers'] = Worker::with('user:id,fullname')->get(['id', 'user_id']);
    }

    if ($projectType == 1 && $activeStep >= 5) {
        $data['designPackages'] = \App\Models\DesignPackage::orderBy('name')
            ->orderBy('price_meter')->get();
    }

    if ($projectType == 2 && $activeStep >= 5) {
        $data['rabPackages'] = \App\Models\RabPackage::orderBy('name')
            ->orderBy('price_meter')->get();
    }

    if (in_array($projectType, [2, 3]) && $activeStep >= 5) {
        $data['jobCategories'] = JobCategory::orderBy('kode_urut')
            ->orderBy('nama_pekerjaan')->get();
    }

    if ($activeStep >= 5 && $project?->customer_id) {

        $data['rabProcesses'] = RabProcess::whereHas('project', function ($q) use ($project) {
            $q->where('customer_id', $project->customer_id);
        })->get();
        $data['rabItemsGrouped'] = RabProcessItem::whereHas('rab.project', function ($q) use ($project) {
                $q->where('customer_id', $project->customer_id);
            })
            ->orderBy('order_no')
            ->get()
            ->unique(function ($item) {
                return $item->floor_name.'|'.$item->category_name.'|'.$item->description.'|'.$item->job_name.'|'.$item->volume.'|'.$item->satuan;
            })
            ->groupBy(['floor_name', 'category_name', 'description']);
    }
    return array_merge($data, $merge);
}
    public function invoicePanel(Project $project)
{
    $project->load('invoiceBuilds');

    return view('projects.partials.invoice_panel',
    compact('project'));
}

public function loadTambahan(BuildProcessItem $item)
{
    $item->load([
        'tambahan.weeklyProgresses'
    ]);

    $jobCategories = JobCategory::select(
        'id',
        'nama_pekerjaan'
    )->get();

    return view(
        'projects.partials.tambahan_rows',
        [
            'item' => $item,
            'jobCategories' => $jobCategories,
            'weekLabels' => $item->project->week_labels,
        ]
    )->render();
}
public function syncBuildPlan(Project $project)
{
    app(BuildPlanSyncService::class)->syncFull($project);
    app(BuildProcessSyncService::class)->syncFull($project);

    $project->update([
        'need_sync_build' => false
    ]);

    return back()->with(
        'success',
        'Build Plan dan Build Process berhasil disinkronkan.'
    );
}
public function syncBuildProcess(Project $project)
{
    app(BuildProcessSyncService::class)
        ->syncFull($project);

    $project->update([
        'need_sync_build' => false
    ]);
    return back()->with(
        'success',
        'Build process berhasil disinkronkan.'
    );
}
public function data(Project $project)
{
    $weeks = $project->week_labels;
    $query = BuildPlans::query()
        ->with('weeks')
        ->where('project_id', $project->id)
        ->ordered();

    $dataTable = DataTables::eloquent($query)
        ->addIndexColumn()
        ->addColumn('bobot_format', function ($row) {
            return number_format(
                $row->bobot_percent,
                3,
                '.',
                ''
            );
        })
        ->addColumn('week_values', function ($row) use ($weeks) {
            $values = [];
            foreach ($weeks as $week) {
                $progress = $row->weeks
                    ->firstWhere('week_no', $week['week_no']);
                $values[$week['week_no']] = $progress?->plan_percent ?? 0;
            }
            return $values;
        });

    $plans = BuildPlans::with('weeks')
        ->where('project_id', $project->id)
        ->get();

    $weekTotal = [];
    foreach ($weeks as $week) {
        $weekTotal[$week['week_no']] = $plans->sum(function ($plan) use ($week) {
            $weekPlan = $plan->weeks->firstWhere('week_no', $week['week_no']);
            return $weekPlan?->plan_percent ?? 0;
        });
    }

    $kumulatif = [];
    $running = 0;
    foreach ($weekTotal as $week => $total) {
        $running += $total;
        $kumulatif[$week] = $running;
    }

    return $dataTable
        ->with([
            'week_total' => $weekTotal,
            'week_kumulatif' => $kumulatif,
        ])
        ->make(true);
}

private function resolveBuildPlanData($project): array
{
    $canEdit = auth()->user()->can('lihat daftar proyek');

    $buildPlans = BuildPlans::query()
        ->where('project_id', $project->id)
        ->with('weeks:id,build_plan_id,week_no,plan_percent')
        ->orderBy('floor_name')
        ->orderBy('category_name')
        ->orderBy('order_no')
        ->get();

    $buildPlans->each(function ($item) {
        $item->progress_map = $item->weeks
            ->keyBy('week_no');
    });

    $groupedPlans = $buildPlans
        ->sortBy([
            ['floor_name', 'asc'],
            ['category_name', 'asc'],
            ['order_no', 'asc'],
        ])
        ->groupBy(function ($item) {
            return $item->floor_name ?: 'Tanpa Lantai';
        })
        ->map(function ($floorItems, $floorName) {

            return [
                'floor_name' => $floorName,

                'categories' => $floorItems
                    ->groupBy(function ($item) {
                        return $item->category_name ?: 'Tanpa Kategori';
                    })
                    ->map(function ($items, $categoryName) {

                        return [
                            'category_name' => $categoryName,

                            'items' => $items
                                ->sortBy('order_no')
                                ->values(),
                        ];
                    })
                    ->values(),
            ];
        })
        ->values();


    return compact(
        'buildPlans',
        'groupedPlans',
        'canEdit'
    );
}

// Route: GET /projects/{project}/build-process-data
public function buildProcessPartial(Project $project)
{
    $project->load([
        'city',
        'weeklyPlans',
        'buildItems.weeklyProgresses',
        'buildItems.tambahan.weeklyProgresses',
        'dailyReports.works.rabProcessItem',
        'dailyReports.workers.worker.user',
        'dailyReports.materials',
    ]);

    $buildData = $this->resolveBuildData($project);

    $formData = $this->formData(
        $project,
        8,
        3
    );

    $canEdit = auth()->user()->can('lihat daftar proyek');
    $isReadOnly = !$canEdit;

    return view(
        'projects.steps.build-process',
        array_merge(
            $buildData,
            $formData,
            compact(
                'project',
                'canEdit',
                'isReadOnly'
            )
        )
    );
}
}