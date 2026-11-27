<?php

namespace App\Http\Controllers;

use App\Models\FinanceTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceTargetController extends Controller
{
public function store(Request $request)
{
    $data = $request->validate([
        'year'             => 'required|integer|min:2000|max:2100',
        'targets.omset.*'  => 'nullable|numeric|min:0',
        'targets.cost.*'   => 'nullable|numeric|min:0',
        'targets.profit.*' => 'nullable|numeric',
    ]);

    DB::transaction(function () use ($data) {
        foreach (FinanceTarget::TIPE as $tipe) {
            for ($m = 1; $m <= 12; $m++) {
                FinanceTarget::updateOrCreate(
                    ['year' => $data['year'], 'type' => $tipe, 'month' => $m],
                    ['target' => (float) ($data['targets'][$tipe][$m] ?? 0)]
                );
            }
        }
    });

    return back()->with('success', 'Target berhasil disimpan.');
}
}