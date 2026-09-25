<?php

namespace App\Http\Controllers;

use App\Models\BuildTermin;
use App\Models\Project;
use App\Models\ProjectLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildTerminController extends Controller
{
public function store(Request $request, $projectId)
{
    abort_if(
        auth()->user()->cannot('ubah data proyek'),
        403
    );

    $project = Project::with([
        'rab',
        'levels',
    ])->findOrFail($projectId);

    // Gak perlu cek project_type/code lagi. Yang nentuin project ini boleh
    // akses fitur termin atau nggak adalah: apakah dia PUNYA level "Setting Termin"
    // di levels-nya (artinya project_type dia memang ditemplate-in step ini lewat
    // project_type_levels). Kalau nggak ada, berarti tipe project ini memang gak
    // pakai skema termin — 404 apa adanya, gak perlu whitelist kode per tipe.
    $currentLevel = $project->levels->firstWhere('level_name', 'Setting Termin');

    if (!$currentLevel) {
        abort(404);
    }

    if (!$project->rab) {
        return back()->withErrors([
            'termin' =>
                'Penawaran Harga belum tersedia.'
        ]);
    }

    $validated = $request->validate([

        'percentage' => [
            'required',
            'array',
            'min:1',
        ],

        'percentage.*' => [
            'required',
            'numeric',
            'min:0.01',
            'max:100',
        ],

        'termin_description' => [
            'nullable',
            'array',
        ],

        'termin_description.*' => [
            'nullable',
            'string',
            'max:255',
        ],

    ]);

    $totalPercentage = collect(
        $validated['percentage']
    )->sum(
        fn ($percentage) => (float) $percentage
    );


    if (abs($totalPercentage - 100) > 0.01) {

        return back()
            ->withErrors([
                'percentage' =>
                    'Total persentase termin harus tepat 100%.'
            ])
            ->withInput();
    }
    $existingTermins = $project->buildTermins()->exists();

    if ($existingTermins) {
        return back()->withErrors([
            'termin' =>
                'Setting termin sudah tersedia. Gunakan fitur edit termin.'
        ]);
    }

    DB::beginTransaction();

    try {

        $offerTotal = (float) $project->rab->grand_total;

        foreach ($validated['percentage'] as $index => $percentage) {

            $percentage = (float) $percentage;

            $amount = $offerTotal * ($percentage / 100);

            BuildTermin::create([
                'project_id'  => $project->id,
                'termin_no'   => $index + 1,
                'percentage'  => $percentage,
                'amount'      => $amount,
                'description' => $validated['termin_description'][$index] ?? null,
            ]);
        }

        // $currentLevel udah kita ambil di atas (guard clause), tinggal pakai lagi di sini
        ProjectLevel::where('id', $currentLevel->id)
            ->update(['is_completed' => true]);

        // Tandai level BERIKUTNYA (relatif +1, bukan angka hardcode) sebagai mulai dikerjakan
        ProjectLevel::where('project_id', $project->id)
            ->where('level_order', $currentLevel->level_order + 1)
            ->update(['is_started' => true]);


        DB::commit();


        return redirect()
            ->route(
                'projects.create',
                [
                    'project_id' =>
                        $project->id,
                ]
            )
            ->with(
                'success',
                'Setting termin berhasil disimpan.'
            );


    } catch (\Throwable $e) {

        DB::rollBack();

        \Log::error(
            'Gagal menyimpan setting termin',
            [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]
        );


        return back()
            ->withErrors([
                'termin' =>
                    'Terjadi kesalahan saat menyimpan setting termin.'
            ])
            ->withInput();
    }
}

    public function update(Request $request, $projectId)
{
    abort_if(
        auth()->user()->cannot('ubah data proyek'),
        403
    );

    $project = Project::with([
        'rab',
        'levels',
    ])->findOrFail($projectId);

    if ((int) $project->project_type !== 3) {
        abort(404);
    }

    if (!$project->rab) {
        return back()->withErrors([
            'termin' => 'Penawaran Build belum tersedia.'
        ]);
    }

    $validated = $request->validate([

        'percentage' => [
            'required',
            'array',
            'min:1',
        ],

        'percentage.*' => [
            'required',
            'numeric',
            'min:0.01',
            'max:100',
        ],

        'termin_description' => [
            'nullable',
            'array',
        ],

        'termin_description.*' => [
            'nullable',
            'string',
            'max:255',
        ],

    ]);

    $totalPercentage = collect(
        $validated['percentage']
    )->sum(
        fn ($percentage) => (float) $percentage
    );

    if (abs($totalPercentage - 100) > 0.01) {

        return back()
            ->withErrors([
                'percentage' =>
                    'Total persentase termin harus tepat 100%.'
            ])
            ->withInput();
    }

    DB::beginTransaction();

    try {

        $offerTotal = (float) $project->rab->grand_total;

        BuildTermin::where(
            'project_id',
            $project->id
        )->delete();

        foreach ($validated['percentage'] as $index => $percentage) {

            $percentage = (float) $percentage;

            $amount = $offerTotal * ($percentage / 100);

            BuildTermin::create([
                'project_id'  => $project->id,
                'termin_no'   => $index + 1,
                'percentage'  => $percentage,
                'amount'      => $amount,
                'description' =>
                    $validated['termin_description'][$index] ?? null,
            ]);
        }

        DB::commit();

        return redirect()
            ->route(
                'projects.create',
                [
                    'project_id' => $project->id,
                ]
            )
            ->with(
                'success',
                'Setting termin Build berhasil diperbarui.'
            );

    } catch (\Throwable $e) {

        DB::rollBack();

        \Log::error(
            'Gagal memperbarui setting termin Build',
            [
                'project_id' => $project->id,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]
        );

        return back()
            ->withErrors([
                'termin' =>
                    'Terjadi kesalahan saat memperbarui setting termin.'
            ])
            ->withInput();
    }
}
}