<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmJadwalReaktivasi;

class ReaktivasiController extends Controller
{
    /** Follow-up yang jatuh tempo hari ini atau sudah lewat dan belum dikerjakan. */
    public function index()
    {
        $jadwal = CrmJadwalReaktivasi::with('customer.user')
            ->where('status', 'menunggu')
            ->whereDate('tanggal_jadwal', '<=', today())
            ->orderBy('tanggal_jadwal')
            ->get();

        return view('crm.reaktivasi.index', compact('jadwal'));
    }

    public function selesai(CrmJadwalReaktivasi $jadwal)
    {
        $jadwal->update(['status' => 'selesai']);

        return back()->with('success', 'Jadwal ditandai selesai.');
    }
}
