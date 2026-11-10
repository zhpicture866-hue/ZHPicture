<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmFeedback;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class CustomerDashboardStats
{
    /** Customer "berisiko" = pernah punya project, tetapi tidak ada project baru dalam N hari. */
    public static function hariBerisiko(): int
    {
        return (int) config('crm.at_risk_days', 90);
    }

    /** Batasi query Customer ke yang berisiko, dihitung per tanggal tertentu (default hari ini). */
    public static function scopeBerisiko(Builder $query, ?Carbon $per = null): Builder
    {
        $per   = $per ?: now();
        $mulai = $per->copy()->subDays(self::hariBerisiko());

        return $query
            ->whereHas('projects', fn ($p) => $p->where('created_at', '<=', $per))
            ->whereDoesntHave('projects', fn ($p) => $p->where('created_at', '>=', $mulai)->where('created_at', '<=', $per));
    }

    public function kartu(): array
    {
        $awalBulan = now()->startOfMonth();
        $awalLalu  = $awalBulan->copy()->subMonth();

        $total      = Customer::count();
        $totalLalu  = Customer::where(fn ($q) => $q->whereNull('created_at')->orWhere('created_at', '<', $awalBulan))->count();
        $baru       = Customer::where('created_at', '>=', $awalBulan)->count();
        $baruLalu   = Customer::where('created_at', '>=', $awalLalu)->where('created_at', '<', $awalBulan)->count();
        $berisiko   = self::scopeBerisiko(Customer::query())->count();
        $berisikoLalu = self::scopeBerisiko(Customer::query(), $awalBulan)->count();

        $ratingSemua  = CrmFeedback::whereNotNull('rating')->avg('rating');
        $ratingIni    = CrmFeedback::whereNotNull('rating')->where('tanggal', '>=', $awalBulan)->avg('rating');
        $ratingLalu   = CrmFeedback::whereNotNull('rating')->where('tanggal', '>=', $awalLalu)->where('tanggal', '<', $awalBulan)->avg('rating');

        return [
            $this->kartuAngka('Total customer', number_format($total, 0, ',', '.'), 'ti-users', 'blue',
                $this->persen($total, $totalLalu), true),
            $this->kartuAngka('Customer baru', number_format($baru, 0, ',', '.'), 'ti-user-plus', 'green',
                $this->persen($baru, $baruLalu), true, 'bulan ini'),
            $this->kartuAngka('Kepuasan rata-rata', $ratingSemua !== null ? number_format($ratingSemua, 1, ',', '.') . ' / 5' : '-', 'ti-mood-smile', 'yellow',
                ($ratingIni !== null && $ratingLalu !== null) ? $this->selisih($ratingIni - $ratingLalu) : null, true,
                $ratingSemua === null ? 'belum ada survey/review' : null),
            $this->kartuAngka('Customer berisiko', number_format($berisiko, 0, ',', '.'), 'ti-alert-triangle', 'red',
                $this->persen($berisiko, $berisikoLalu), false, 'tanpa project ' . self::hariBerisiko() . ' hari'),
        ];
    }

    private function kartuAngka(string $judul, string $nilai, string $ikon, string $warnaIkon, ?array $tren, bool $naikBaik, ?string $catatan = null): array
    {
        $warna = 'secondary';
        $panah = 'ti-minus';
        if ($tren) {
            if ($tren['arah'] > 0) {
                $panah = 'ti-trending-up';
                $warna = $naikBaik ? 'green' : 'red';
            } elseif ($tren['arah'] < 0) {
                $panah = 'ti-trending-down';
                $warna = $naikBaik ? 'red' : 'green';
            }
        }

        return [
            'judul'      => $judul,
            'nilai'      => $nilai,
            'ikon'       => $ikon,
            'warna_ikon' => $warnaIkon,
            'perubahan'  => $tren['teks'] ?? null,
            'warna'      => $warna,
            'panah'      => $panah,
            'keterangan' => $catatan ?: ($tren ? 'vs bulan lalu' : 'belum ada pembanding bulan lalu'),
        ];
    }

    /** Perubahan persen; null bila bulan lalu 0 (tidak bisa dibandingkan). */
    private function persen(int|float $sekarang, int|float $lalu): ?array
    {
        if ($lalu <= 0) {
            return null;
        }
        $p = round(($sekarang - $lalu) / $lalu * 100, 1);

        return ['arah' => $p <=> 0, 'teks' => ($p > 0 ? '+' : '') . str_replace('.', ',', (string) $p) . '%'];
    }

    private function selisih(float $poin): array
    {
        $p = round($poin, 1);

        return ['arah' => $p <=> 0, 'teks' => ($p > 0 ? '+' : '') . str_replace('.', ',', (string) $p) . ' poin'];
    }
}
