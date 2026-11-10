<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmFollowup;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FollowupController extends Controller
{
    public function store(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'kanal'             => ['required', Rule::in(array_keys(config('crm.kanal')))],
            'catatan'           => ['required', 'string'],
            'jadwal_berikutnya' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'catatan.required'                  => 'Catatan follow-up wajib diisi.',
            'jadwal_berikutnya.after_or_equal'  => 'Jadwal berikutnya tidak boleh sebelum hari ini.',
        ]);

        $customer->crmFollowups()->create($data + ['staff_id' => $request->user()?->id]);

        return back()->with('success', 'Follow-up dicatat.');
    }

    public function selesai(CrmFollowup $followup)
    {
        $followup->update(['status' => 'selesai']);

        return back()->with('success', 'Follow-up ditandai selesai.');
    }
}
