<?php

namespace App\Http\Controllers;

use App\Models\OfferProcess;
use App\Models\OfferProcessItem;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Services\ProjectNotifier;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RabProcessController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_id' => [
                'required',
                'uuid',
                Rule::exists(Project::class, 'id'),
            ],

            'profit' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'overhead' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'subtotal' => [
                'required',
                'numeric',
                'min:0',
            ],

            'discount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'subtotal_after_discount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'tax_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'tax_total' => [
                'required',
                'numeric',
                'min:0',
            ],

            'shipping' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'grand_total' => [
                'required',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.job_name' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.description' => [
                'nullable',
                'string',
            ],

            'items.*.satuan' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.volume' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.base_price' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.total' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.order_no' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'items.*.category_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.job_category_id' => [
                'nullable',
                'integer',
            ],
        ]);

        DB::beginTransaction();

        try {

            $project = Project::findOrFail(
                $validated['project_id']
            );

            /*
            * Data project
            *
            * Sesuaikan nama field ini jika struktur
            * tabel projects kamu berbeda.
            */
            $contactName = $project->contact_name ?? '';
            $jobLocation = $project->job_location ?? '';

            /*
            * Buat header offer/RAB
            */
            $offerProcess = OfferProcess::create([
                'project_id' => $project->id,

                'contact_name' => $contactName,

                'job_location' => $jobLocation,

                'job_duration' => null,

                'profit' => $validated['profit'] ?? 0,

                'overhead' => $validated['overhead'] ?? 0,

                'base_subtotal' => $validated['subtotal'] ?? 0,

                'subtotal' => $validated['subtotal'] ?? 0,

                'discount' => $validated['discount'] ?? 0,

                'subtotal_after_discount' =>
                    $validated['subtotal_after_discount'] ?? 0,

                'tax_rate' => $validated['tax_rate'] ?? 0,

                'tax_total' => $validated['tax_total'] ?? 0,

                'shipping' => $validated['shipping'] ?? 0,

                'grand_total' => $validated['grand_total'] ?? 0,

                'notes' => $validated['notes'] ?? null,

                'created_by' => auth()->id(),

                'updated_by' => auth()->id(),
            ]);

            /*
            * Simpan detail RAB
            */
            foreach ($validated['items'] as $index => $item) {

                RabProcessItem::create([
                    'offer_process_id' => $offerProcess->id,

                    'job_category_id' =>
                        $item['job_category_id'] ?? null,

                    'job_name' =>
                        $item['job_name'],

                    'satuan' =>
                        $item['satuan'],

                    'volume' =>
                        $item['volume'],

                    'base_price' =>
                        $item['base_price'] ?? 0,

                    'price' =>
                        $item['price'],

                    'total' =>
                        $item['total'],

                    'profit' =>
                        $validated['profit'] ?? 0,

                    'overhead' =>
                        $validated['overhead'] ?? 0,

                    'order_no' =>
                        $item['order_no'] ?? ($index + 1),

                    'category_name' =>
                        $item['category_name'] ?? null,

                    'description' =>
                        $item['description'] ?? null,

                    'is_draft' => false,
                ]);
            }
            $project = Project::findOrFail(
            $request->project_id
        );

        /*
        |--------------------------------------------------------------------------
        | PROJECT LEVEL
        |--------------------------------------------------------------------------
        |
        | Level 6 = Proses Pengerjaan RAB
        | Setelah RAB selesai, level 6 selesai.
        | Kemudian level berikutnya dimulai.
        |
        */

        $currentLevel = $project->levels()
            ->where('level_order', 6)
            ->first();

        $nextLevel = $project->levels()
            ->where('level_order', '>', 6)
            ->orderBy('level_order')
            ->first();

        if ($currentLevel && !$currentLevel->is_completed) {

            $currentLevel->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | MULAI LEVEL BERIKUTNYA
        |--------------------------------------------------------------------------
        */

        if ($nextLevel) {

            $nextLevel->update([
                'is_started' => true,
                'started_at' => $nextLevel->started_at ?? now(),
            ]);

            /*
             * Active step mengikuti level berikutnya
             */
            $project->update([
                'active_step' => $nextLevel->level_order,
            ]);

        } else {

            /*
             * Kalau tidak ada level berikutnya,
             * active_step tetap di level terakhir.
             */
            $project->update([
                'active_step' =>
                    $currentLevel?->level_order ?? 6,
            ]);
        }
            DB::commit();

            
            $this->notifyProjectEvent(
                $project,
                'rab_created'
            );
            return redirect()
                ->back()
                ->with(
                    'success',
                    'RAB berhasil disimpan.'
                );

        } catch (\Throwable $e) {

            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'RAB gagal disimpan: ' . $e->getMessage()
                );
        }
    }

    protected function notifyProjectEvent(Project $project, string $event)
{
    $cfg = config("project_events.$event");
    if (!$cfg) return;

    $admin    = auth()->user();
    $customer = $project->customer?->user;

    $targets = [];

    if ($admin) {
        $targets['admin'] = $admin;
    }

    if ($customer) {
        $targets['customer'] = $customer;
    }

    foreach ($targets as $role => $user) {
        if (!isset($cfg['message'][$role])) continue;

        ProjectNotifier::notifyUsers(
            [$user],
            ProjectNotifier::makePayload($project, [
                'type'    => $event,
                'role'    => $role,
                'title'   => $cfg['title'],
                'message' => $cfg['message'][$role],
                'url'     => route('projects.create', ['project_id' => $project->id]),
            ])
        );
    }
}
}