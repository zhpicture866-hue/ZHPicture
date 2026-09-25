<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\InvoiceBuild;
use App\Models\ProjectLevel;
use App\Models\BuildProcessItem;
use App\Models\BuildPlans;
use App\Models\BuildWeeklyProgress;
use App\Services\ProjectNotifier;
use App\Services\InvoiceBuildNumberGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use DB;

class InvoiceBuildController extends Controller
{

public function invoiceBuild(Project $project, int $termin)
{
    abort_if(!$project->rab, 404);

    Carbon::setLocale('id');
    $buildTermin = $project->buildTermins()
        ->where('termin_no', $termin)
        ->first();

    abort_if(
        !$buildTermin,
        404,
        'Termin Build tidak ditemukan.'
    );

    $offer = $project->rab;

    $grandTotal = (float) $offer->grand_total;

    $result = DB::transaction(function () use (
        $project,
        $termin,
        $buildTermin,
        $grandTotal
    ) {

        $paymentPercentage = (float) $buildTermin->percentage;
        $newAmount = (float) $buildTermin->amount;
        $termins = $project->buildTermins()
            ->orderBy('termin_no')
            ->get();

        $progressStart = 0;

        foreach ($termins as $item) {

            if ((int) $item->termin_no === $termin) {
                break;
            }

            $progressStart += (float) $item->percentage;
        }

        $progressEnd = $progressStart + $paymentPercentage;

        $invoice = InvoiceBuild::where('project_id', $project->id)
            ->where('termin', $termin)
            ->lockForUpdate()
            ->first();

        if (!$invoice) {

            $invoice = InvoiceBuild::create([
                'project_id'         => $project->id,
                'invoice_type'       => InvoiceBuild::TYPE_WEDDING,
                // 'invoice_number'     => InvoiceBuildNumberGenerator::generate($termin),
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date'       => now(),
                'termin'             => $termin,
                'progress_start'     => $progressStart,
                'progress_end'       => $progressEnd,
                'payment_percentage' => $paymentPercentage,
                'amount'             => $newAmount,
                'status'             => 'waiting',
            ]);

        } else {

            if (
                (float) $invoice->amount !== $newAmount ||
                (float) $invoice->payment_percentage !== $paymentPercentage ||
                (float) $invoice->progress_start !== $progressStart ||
                (float) $invoice->progress_end !== $progressEnd
            ) {

                $invoice->update([
                    'amount'             => $newAmount,
                    'payment_percentage' => $paymentPercentage,
                    'progress_start'     => $progressStart,
                    'progress_end'       => $progressEnd,
                ]);
            }
        }

        if (!$invoice->downloaded_at) {

            $invoice->update([
                'downloaded_at' => now(),
            ]);
        }

        return [
            'invoice'    => $invoice->fresh(),
            'grandTotal' => $grandTotal,
        ];
    });

    return Pdf::loadView('invoice.build', [
        'invoice'    => $result['invoice'],
        'project'    => $project,
        'offer'      => $offer,
        'grandTotal' => $result['grandTotal'],
    ])
    ->setPaper('A4', 'portrait')
    ->stream(
        "Invoice-Build-Termin-{$termin}-{$project->project_name}.pdf"
    );
}

public function approve(Project $project, InvoiceBuild $invoice)
{
    // Pastikan invoice memang milik project ini
    abort_if(
        $invoice->project_id !== $project->id,
        404
    );

    // Authorization
    if (
        $project->customer?->user_id !== auth()->id()
        && auth()->user()->cannot('lihat daftar proyek')
    ) {
        abort(403);
    }

    // Invoice harus sudah pernah dibuka/download
    if (!$invoice->downloaded_at) {
        return back()->with(
            'error',
            'Invoice belum didownload.'
        );
    }

    // Jangan approve ulang
    if ($invoice->approved_at) {
        return back()->with(
            'info',
            'Invoice sudah disetujui.'
        );
    }

    $currentTermin = (int) $invoice->termin;

    // Pastikan termin sebelumnya sudah approved
    if ($currentTermin > 1) {

        $previousInvoice = InvoiceBuild::where('project_id', $project->id)
            ->where('termin', $currentTermin - 1)
            ->first();

        abort_if(
            !$previousInvoice || !$previousInvoice->approved_at,
            403,
            'Termin sebelumnya belum disetujui.'
        );
    }

    DB::transaction(function () use (
        $project,
        $invoice,
        $currentTermin
    ) {

        $invoice->update([
            'status'         => 'approved',
            'approved_at'    => now(),
            'approve_by_name' => auth()->user()->fullname ?? 'Customer',
            'approved_ip'    => request()->ip(),
        ]);

        $lastTermin = (int) $project->buildTermins()->max('termin_no');

        if ($currentTermin === $lastTermin) {

            // Selesaikan level Invoice
            $invoiceLevel = $project->levels()
                ->where('level_name', 'Invoice')
                ->first();

            if ($invoiceLevel && !$invoiceLevel->is_completed) {
                $invoiceLevel->update([
                    'is_completed' => true,
                    'completed_at' => now(),
                ]);
            }

            // $nextLevel = $project->levels()
            //     ->where('level_order', '>', $invoiceLevel?->level_order)
            //     ->orderBy('level_order')
            //     ->first();

            // if ($nextLevel) {
            //     $nextLevel->update([
            //         'is_started' => true,
            //         'started_at' => $nextLevel->started_at ?? now(),
            //     ]);

            //     $project->update([
            //         'active_step' => $nextLevel->level_order + 1,
            //     ]);
            // } 
        }
    });

    $event = 'invoice_build_created';

    $cfg = config("project_events.$event");

    if (!$cfg) {
        throw new \Exception(
            "Config project_events.$event not found"
        );
    }

    $payloadExtra = [
        'termin' => $invoice->termin,

        'amount' => number_format(
            $invoice->amount,
            0,
            ',',
            '.'
        ),

        'progress_start' => $invoice->progress_start,

        'progress_end' => $invoice->progress_end,
    ];


    ProjectNotifier::notifyUsers(
        [
            $project->createdBy
                ?? auth()->user()
        ],
        ProjectNotifier::makePayload(
            $project,
            [
                'type' => $event,

                'role' => 'Super-Admin',

                'title' => ProjectNotifier::parseMessage(
                    $cfg['title'],
                    $payloadExtra
                ),

                'message' => ProjectNotifier::parseMessage(
                    $cfg['message']['Super-Admin'],
                    $payloadExtra
                ),

                'url' => route(
                    'projects.create',
                    [
                        'project_id' => $project->id
                    ]
                ),
            ]
        )
    );


    if ($project->customer?->user) {

        ProjectNotifier::notifyUsers(
            [
                $project->customer->user
            ],
            ProjectNotifier::makePayload(
                $project,
                [
                    'type' => $event,

                    'role' => 'Customer',

                    'title' => ProjectNotifier::parseMessage(
                        $cfg['title'],
                        $payloadExtra
                    ),

                    'message' => ProjectNotifier::parseMessage(
                        $cfg['message']['customer'],
                        $payloadExtra
                    ),

                    'url' => route(
                        'projects.create',
                        [
                            'project_id' => $project->id
                        ]
                    ),
                ]
            )
        );
    }


    return redirect()
        ->route(
            'projects.create',
            [
                'project_id' => $project->id
            ]
        )
        ->with(
            'success',
            "Invoice Termin {$invoice->termin} berhasil disetujui."
        );
}

public static function autoGenerate(Project $project, $progress)
{

    $buffer = 10; // 2%

    $terminMap = [
        1 => 0,
        2 => 30,
        3 => 60,
        4 => 90,
    ];

    foreach ($terminMap as $termin => $targetProgress) {

        $triggerProgress = max(0, $targetProgress - $buffer);

        if($progress >= $triggerProgress){

            InvoiceBuild::firstOrCreate([
                'project_id'=>$project->id,
                'termin'=>$termin
            ],[

                'invoice_type'=>InvoiceBuild::TYPE_BUILD,

                'invoice_number'=>InvoiceBuildNumberGenerator::generate($termin),

                'invoice_date'=>now(),

                'progress_start'=>$targetProgress,

                'progress_end'=>match($termin){
                    1=>30,
                    2=>60,
                    3=>90,
                    4=>100
                },

                'payment_percentage'=>match($termin){
                    1=>30,
                    2=>30,
                    3=>30,
                    4=>10
                },

                'amount'=>$project->offer->grand_total * (
                    match($termin){
                        1=>0.30,
                        2=>0.30,
                        3=>0.30,
                        4=>0.10
                    }
                ),

                'status'=>'waiting'
            ]);

        }

    }

}

public function invoiceJustek(Project $project)
{
    $invoice = InvoiceBuild::where([
        'project_id'=>$project->id,
        'invoice_type'=>'justek'
    ])->firstOrFail();

    $justekRows = BuildWeeklyProgress::whereHas('item', function ($q) use ($project) {
            $q->where('project_id', $project->id);
        })
        ->where(function ($q) {
            $q->where('just_tambah', '>', 0)
              ->orWhere('just_kurang', '>', 0)
              ->orWhere('just_baru', '>', 0);
        })
        ->with('item')
        ->get();
    // $justekRows = BuildWeeklyProgress::whereHas('item', function ($q) use ($project) {
    //     $q->where('project_id', $project->id);
    // })
    // ->where(function ($q) {
    //     $q->where('just_tambah', '>', 0)
    //       ->orWhere('just_kurang', '>', 0)
    //       ->orWhere('just_baru', '>', 0);
    // })
    // ->with('item')
    // ->get()
    // ->groupBy('build_process_item_id');

    $grandTotal = $justekRows->sum(function ($row) {
        $price = optional($row->item)->price ?? 0;

        return 
            ($row->just_tambah * $price)
        + ($row->just_baru * $price)
        - ($row->just_kurang * $price);
    });

    return Pdf::loadView('invoice.build-justek', [
        'invoice'=>$invoice,
        'project'=>$project,
        'offer'=>$project->offer,
        'justekRows'=>$justekRows,
        'grandTotal'=>$grandTotal
    ])->stream("Invoice-Justek-{$project->project_name}.pdf");
}
public function autoJustek(Project $project)
{
    $nilaiJustek = $project->buildItems()
        ->with('weeklyProgresses')
        ->get()
        ->sum(function($item){

            return $item->weeklyProgresses->sum(function($w){

                return ($w->just_tambah ?? 0)
                     - ($w->just_kurang ?? 0)
                     + ($w->just_baru ?? 0);

            });

        });

    if($nilaiJustek <= 0){
        return response()->json(['ok'=>false]);
    }

    $exist = InvoiceBuild::where('project_id',$project->id)
        ->where('invoice_type','justek')
        ->first();

    if(!$exist){

        InvoiceBuild::create([
            'project_id'=>$project->id,
            'invoice_type'=>'justek',
            'termin'=>0,
            'nominal'=>$nilaiJustek,
            'status'=>'draft'
        ]);

    }

    return response()->json(['ok'=>true]);
}
    private function generateInvoiceNumber(): string
{
    $year = now()->format('Y');

    $lastInvoice = InvoiceBuild::where('invoice_number', 'like', "ZH.I.{$year}.%")
        ->orderByDesc('invoice_number')
        ->first();

    if ($lastInvoice) {
        $lastNumber = (int) substr($lastInvoice->invoice_number, -2);
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }

    return sprintf(
        'ZH.I.%s.%02d',
        $year,
        $nextNumber
    );
}
}