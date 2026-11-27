<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardFinanceController extends Controller
{
    /** Awalan account_code yang dihitung sebagai Cost (HPP + beban). Sesuaikan dengan bagan akun. */
    private const COST_PREFIX = ['5', '6'];

    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        $omsetLalu = $this->omsetPerBulan($year - 1);
        $omsetIni  = $this->omsetPerBulan($year);
        $costLalu  = $this->costPerBulan($year - 1);
        $costIni   = $this->costPerBulan($year);

        // Profit = omset - cost (per bulan)
        $profitLalu = [];
        $profitIni  = [];
        for ($m = 1; $m <= 12; $m++) {
            $profitLalu[$m] = ($omsetLalu[$m] ?? 0) - ($costLalu[$m] ?? 0);
            $profitIni[$m]  = ($omsetIni[$m] ?? 0)  - ($costIni[$m] ?? 0);
        }

        // Target per jenis: ['omset' => [1 => ..., 12 => ...], 'cost' => ..., 'profit' => ...]
        $targets = DB::table('zhpicture.finance_targets')
            ->where('year', $year)
            ->get()
            ->groupBy('type')
            ->map(fn ($g) => $g->pluck('target', 'month'));

        return view('dashboard.finance', [
            'year'  => $year,
            'tabel' => [
                'omset'  => $this->bangunTabel($year, $omsetLalu,  $omsetIni,  $targets['omset']  ?? collect()),
                'cost'   => $this->bangunTabel($year, $costLalu,   $costIni,   $targets['cost']   ?? collect()),
                'profit' => $this->bangunTabel($year, $profitLalu, $profitIni, $targets['profit'] ?? collect()),
            ],
        ]);
    }

    /**
     * Susunan tabel yang sama untuk Omset, Cost, dan Profit.
     * Tiap baris: label, lalu (tahun lalu), target, real (realisasi), progress, tumbuh.
     */
    private function bangunTabel(int $year, $lalu, $ini, $target): array
    {
        $now  = now();
        $rows = [];

        for ($m = 1; $m <= 12; $m++) {
            $masaDepan = Carbon::create($year, $m, 1)->startOfMonth()->gt($now);

            $l = (float) ($lalu[$m] ?? 0);
            $t = (float) ($target[$m] ?? 0);
            $r = $masaDepan ? null : (float) ($ini[$m] ?? 0);

            $rows[$m] = $this->hitung($l, $t, $r);
            $rows[$m]['label'] = Carbon::create($year, $m, 1)->translatedFormat('M Y');
        }

        // Kolom total tahun
        $terisi = collect($rows)->whereNotNull('real');          // bulan yang sudah berjalan
        $real   = $terisi->isEmpty() ? null : $terisi->sum('real');

        $total = $this->hitung(
            collect($rows)->sum('lalu'),
            collect($rows)->sum('target'),
            $real,
            $terisi->sum('lalu')   // pertumbuhan dibanding bulan yang sama tahun lalu
        );

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     *  Progress    = realisasi / target * 100
     *  Pertumbuhan = realisasi / tahun lalu * 100
     */
    private function hitung(float $lalu, float $target, ?float $real, ?float $pembanding = null): array
    {
        $banding = $pembanding ?? $lalu;

        return [
            'lalu'     => $lalu,
            'target'   => $target,
            'real'     => $real,
            'progress' => ($real !== null && $target > 0)  ? $real / $target  * 100 : null,
            'tumbuh'   => ($real !== null && $banding > 0) ? $real / $banding * 100 : null,
        ];
    }

    /** Omset: akun Pendapatan (kode diawali "4"), nilai = kredit - debit. */
    private function omsetPerBulan(int $year)
    {
        return $this->nilaiAkunPerBulan($year, ['4'], 'kredit');
    }

    /** Cost: akun HPP/beban (lihat COST_PREFIX), nilai = debit - kredit. */
    private function costPerBulan(int $year)
    {
        return $this->nilaiAkunPerBulan($year, self::COST_PREFIX, 'debit');
    }

    private function nilaiAkunPerBulan(int $year, array $prefix, string $sisi)
    {
        $nilai = $sisi === 'kredit' ? 'SUM(d.credit - d.debit)' : 'SUM(d.debit - d.credit)';

        return DB::table('zhpicture.accounting_journal_details as d')
            ->join('zhpicture.accounting_journals as j', 'j.id', '=', 'd.journal_id')
            ->join('zhpicture.accounting_accounts as a', 'a.id', '=', 'd.account_id')
            ->where(function ($q) use ($prefix) {
                foreach ($prefix as $p) {
                    $q->orWhere('a.account_code', 'like', $p . '%');
                }
            })
            ->where('j.transaction_date', '>=', "$year-01-01")
            ->where('j.transaction_date', '<', ($year + 1) . '-01-01')
            ->selectRaw("EXTRACT(MONTH FROM j.transaction_date)::int as bulan, $nilai as total")
            ->groupByRaw('EXTRACT(MONTH FROM j.transaction_date)')
            ->pluck('total', 'bulan')
            ->map(fn ($v) => (float) $v);
    }
}