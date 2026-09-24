<?php

namespace App\Http\Controllers;

use App\Models\OfferProcess;
use App\Models\OfferProcessItem;
use App\Models\Project;
use Illuminate\Http\Request;
use App\Services\ProjectNotifier;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class OfferProcessController extends Controller
{
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'project_id' => [
            'required',
            'uuid',
            Rule::exists(Project::class, 'id'),
        ],

        'offer_date' => [
            'required',
            'date',
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

        'items.*.description' => [
            'nullable',
            'string',
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
            'min:1',
        ],
    ]);

    if ($validator->fails()) {
        dd([
            'errors' => $validator->errors()->toArray(),
            'request' => $request->all(),
        ]);
    }

    $validated = $validator->validated();


    DB::beginTransaction();

    try {

        $project = Project::findOrFail(
            $validated['project_id']
        );

        $contactName = $project->customer->user->fullname ?? '';

        $offerProcess = OfferProcess::create([

            'project_id' => $project->id,
            'offer_number' => $this->generateOfferNumber(),

            'offer_date' => $validated['offer_date'],
            'contact_name' => $contactName,
            'profit' =>
                $validated['profit'] ?? 0,

            'overhead' =>
                $validated['overhead'] ?? 0,

            'base_subtotal' =>
                $validated['subtotal'] ?? 0,

            'subtotal' =>
                $validated['subtotal'] ?? 0,

            'discount' =>
                $validated['discount'] ?? 0,

            'subtotal_after_discount' =>
                $validated['subtotal_after_discount'] ?? 0,

            'tax_rate' =>
                $validated['tax_rate'] ?? 0,

            'tax_total' =>
                $validated['tax_total'] ?? 0,

            'shipping' =>
                $validated['shipping'] ?? 0,

            'grand_total' =>
                $validated['grand_total'] ?? 0,

            'notes' =>
                $validated['notes'] ?? null,

            'created_by' =>
                auth()->id(),

            'updated_by' =>
                auth()->id(),
        ]);


        foreach ($validated['items'] as $index => $item) {

            OfferProcessItem::create([

                'offer_process_id' =>
                    $offerProcess->id,

                'description' =>
                    $item['description'] ?? null,

                'volume' =>
                    $item['volume'],

                'base_price' =>
                    $item['base_price'] ?? 0,

                'price' =>
                    $item['price'],

                'total' =>
                    $item['total'],

                'order_no' =>
                    $item['order_no'] ?? ($index + 1),

                'is_draft' =>
                    false,
            ]);
        }


        $currentLevel = $project->levels()
            ->where('level_order', 1)
            ->first();

        $nextLevel = $project->levels()
            ->where('level_order', '>', 2)
            ->orderBy('level_order')
            ->first();

        if (
            $currentLevel &&
            !$currentLevel->is_completed
        ) {

            $currentLevel->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);
        }

        if ($nextLevel) {

            $nextLevel->update([

                'is_started' => true,

                'started_at' =>
                    $nextLevel->started_at ?? now(),
            ]);


            $project->update([

                'active_step' =>
                    $nextLevel->level_order,
            ]);

        } else {

            $project->update([

                'active_step' =>
                    $currentLevel?->level_order ?? 2,
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
                'Form Penawaran Harga berhasil disimpan.'
            );


} catch (\Throwable $e) {

    DB::rollBack();

    dd([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);
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
private function generateOfferNumber(): string
{
    $year = now()->format('Y');

    $lastOffer = OfferProcess::query()
        ->whereYear('offer_date', $year)
        ->where('offer_number', 'like', "ZH.Q.{$year}.%")
        ->orderByDesc('id')
        ->first();

    if ($lastOffer) {

        $lastNumber = (int) substr(
            $lastOffer->offer_number,
            strrpos($lastOffer->offer_number, '.') + 1
        );

        $number = $lastNumber + 1;

    } else {

        $number = 1;
    }

    return sprintf(
        'ZH.Q.%s.%02d',
        $year,
        $number
    );
}
public function exportPdf(Project $project)
{
    // sesuaikan nama relasi di model Project
    $offer = $project->rab()
        ->with(['items' => fn ($q) => $q->orderBy('order_no')->orderBy('id')])
        ->latest('id')
        ->first();

    if (!$offer) abort(404);

    $pdf = Pdf::loadView('rab.pdf', compact('offer', 'project'))
        ->setPaper('A4', 'portrait');

    return $pdf->stream('PENAWARAN HARGA-' . $project->project_name . '.pdf');
}
public function structure($id)
{
    $rab = OfferProcess::with([
        'items',
    ])->findOrFail($id);

    return response()->json([
        'meta' => [
            'profit' => $rab->profit,
            'overhead' => $rab->overhead,
            'discount' => $rab->discount,
            'tax_rate' => $rab->tax_rate,
            'shipping' => $rab->shipping,
        ],
        'items' => $rab->items
    ]);
}

public function update(Request $request, Project $project, OfferProcess $rab) {


    if ($rab->project_id !== $project->id) {

        abort(404);

    }

    $validator = Validator::make($request->all(), [

        'project_id' =>
            'required|exists:projects,id',

        'contact_name' =>
            'required|string|max:255',

        'job_location' =>
            'required|string|max:255',

        'job_duration' =>
            'nullable|string',

        'profit' =>
            'nullable|numeric|min:0|max:100',

        'overhead' =>
            'nullable|numeric|min:0|max:100',

        'discount' =>
            'nullable|numeric|min:0',

        'tax_rate' =>
            'nullable|numeric|min:0|max:100',

        'shipping' =>
            'nullable|numeric|min:0',
        'notes' => 'nullable|string',

        /*
        |--------------------------------------------------------------------------
        | ITEMS
        |--------------------------------------------------------------------------
        */

        'items' =>
            'required|array|min:1',

        'items.*.id' =>
            'nullable',

        'items.*.floor_name' =>
            'required|string|max:255',

        'items.*.category_name' =>
            'required|string|max:255',

        'items.*.job_name' =>
            'required|string|max:255',

        'items.*.description' =>
            'nullable|string',

        'items.*.satuan' =>
            'required|string|max:100',

        'items.*.volume' =>
            'required|numeric|gt:0',

        'items.*.base_price' =>
            'required|numeric|min:0',

        'items.*.price' =>
            'nullable|numeric|min:0',

        'items.*.total' =>
            'nullable|numeric|min:0',

        'items.*.order_no' =>
            'required|integer|min:1',
    ]);


    if ($validator->fails()) {

        return redirect()
            ->back()
            ->withErrors($validator)
            ->withInput();

    }


    /*
    |--------------------------------------------------------------------------
    | PROJECT_ID DARI FORM HARUS SESUAI DENGAN ROUTE
    |--------------------------------------------------------------------------
    */

    if ($request->project_id !== $project->id) {

        return redirect()
            ->back()
            ->withInput()
            ->with(
                'error',
                'Project RAB tidak valid.'
            );

    }


    DB::beginTransaction();


    try {

        $profit = (float) ($request->profit ?? 0);

        $overhead = (float) ($request->overhead ?? 0);

        $discount = (float) ($request->discount ?? 0);

        $taxRate = (float) ($request->tax_rate ?? 0);

        $shipping = (float) ($request->shipping ?? 0);

        $baseSubtotal = 0;

        $subtotal = 0;


        foreach ($request->items as $item) {

            $volume = (float) $item['volume'];

            $basePrice = (float) $item['base_price'];

            $baseTotal = $volume * $basePrice;

            $baseSubtotal += $baseTotal;

            $overheadAmount = $basePrice * $overhead / 100;

            $profitAmount = $basePrice * $profit / 100;

            $price = $basePrice + $overheadAmount + $profitAmount;

            $total = $volume * $price;

            $subtotal += $total;
        }

        $subtotalAfterDiscount = max(
            0,
            $subtotal - $discount
        );

        $taxTotal = $subtotalAfterDiscount * $taxRate / 100;

        $grandTotal = $subtotalAfterDiscount + $taxTotal + $shipping;

        $rab->update([

            'contact_name' =>
                $request->contact_name,

            'job_location' => $request->job_location,

            'job_duration' => $request->job_duration,
            'notes' => $request->notes,
            'base_subtotal' => $baseSubtotal,

            'profit' => $profit,

            'overhead' => $overhead,

            'subtotal' => $subtotal,

            'discount' => $discount,

            'subtotal_after_discount' => $subtotalAfterDiscount,

            'tax_rate' => $taxRate,

            'tax_total' => $taxTotal,

            'shipping' => $shipping,

            'grand_total' => $grandTotal,
            'updated_by' => auth()->id(),
        ]);

        $submittedItemIds = [];


        foreach (
            $request->items
            as $index => $item
        ) {

            $volume = (float) $item['volume'];

            $basePrice = (float) $item['base_price'];

            $overheadAmount =
                $basePrice
                * $overhead
                / 100;

            $profitAmount =
                $basePrice
                * $profit
                / 100;


            $price =
                $basePrice
                + $overheadAmount
                + $profitAmount;

            $total = $volume * $price;

            if (!empty($item['id'])) {

                $rabItem = OfferProcessItem::where(
                    'id',
                    $item['id']
                )
                    ->where(
                        'rab_process_id',
                        $rab->id
                    )
                    ->first();

                if (!$rabItem) {

                    throw new \Exception(
                        'Item RAB tidak ditemukan atau tidak sesuai dengan RAB.'
                    );

                }

                $rabItem->update([

                    'floor_name' =>
                        $item['floor_name'],

                    'category_name' =>
                        $item['category_name'],

                    'job_name' =>
                        $item['job_name'],

                    'description' =>
                        $item['description'] ?? null,

                    'satuan' =>
                        $item['satuan'],

                    'volume' =>
                        $volume,

                    'base_price' =>
                        $basePrice,

                    'price' =>
                        $price,

                    'total' =>
                        $total,

                    'order_no' =>
                        $index + 1,
                ]);


                $submittedItemIds[] =
                    $rabItem->id;

            }

            else {

                $rabItem =
                    OfferProcessItem::create([

                        'rab_process_id' =>
                            $rab->id,

                        'floor_name' =>
                            $item['floor_name'],

                        'category_name' =>
                            $item['category_name'],

                        'job_name' =>
                            $item['job_name'],

                        'description' =>
                            $item['description'] ?? null,

                        'satuan' =>
                            $item['satuan'],

                        'volume' =>
                            $volume,

                        'base_price' =>
                            $basePrice,

                        'price' =>
                            $price,

                        'total' =>
                            $total,

                        'order_no' =>
                            $index + 1,
                    ]);


                $submittedItemIds[] =
                    $rabItem->id;
            }
        }

        OfferProcessItem::where(
            'rab_process_id',
            $rab->id
        )
            ->whereNotIn(
                'id',
                $submittedItemIds
            )
            ->delete();


        DB::commit();

        return redirect()
            ->back()
            ->with(
                'success',
                'RAB berhasil diperbarui.'
            );


    } catch (\Throwable $e) {

        DB::rollBack();

        report($e);


        return redirect()
            ->back()
            ->withInput()
            ->with(
                'error',
                'RAB gagal diperbarui: '
                . $e->getMessage()
            );
    }
}
}