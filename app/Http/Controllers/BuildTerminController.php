<?php

namespace App\Http\Controllers;

use App\Models\BuildTermin;
use App\Models\Project;
use App\Models\ProjectLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BuildTerminController extends Controller
{
public function store(Request $request, $projectId)
{
    abort_if(
        auth()->user()->cannot('ubah data proyek'),
        403
    );
 
    $project = Project::with(['rab', 'levels'])->findOrFail($projectId);
 
    $currentLevel = $project->levels->firstWhere('level_name', 'Setting Termin');
 
    abort_if(! $currentLevel, 404);
 
    if (! $project->rab) {
        return back()->withErrors([
            'termin' => 'Penawaran Harga belum tersedia.',
        ]);
    }
 
    // Cek lebih awal: kalau termin sudah ada, tidak perlu validasi input.
    if ($project->buildTermins()->exists()) {
        return back()->withErrors([
            'termin' => 'Setting termin sudah tersedia. Gunakan fitur edit termin.',
        ]);
    }
 
    // Jumlah baris termin dari input, dipakai untuk memastikan
    // semua array (nominal, tanggal, keterangan) sama panjang.
    $terminCount = count((array) $request->input('amount', []));
 
    // NOMINAL adalah sumber kebenaran, bukan persentase — supaya total
    // nominal semua termin selalu pas sama dengan grand_total penawaran,
    // tanpa sisa pembulatan. Persentase hanya dihitung ulang dari nominal
    // untuk disimpan sebagai data tampilan.
    $validated = $request->validate([
        'amount'   => ['required', 'array', 'min:1', 'max:24'],
        'amount.*' => ['required', 'integer', 'min:1'],
 
        'termin_description'   => ['nullable', 'array', 'size:' . $terminCount],
        'termin_description.*' => ['nullable', 'string', 'max:255'],
 
        'billing_date'   => ['required', 'array', 'size:' . $terminCount],
        'billing_date.*' => ['required', 'date'],
    ]);
 
    // Reindex supaya urutan 0..n-1 dan termin_no selalu berurutan.
    $amounts      = array_map('intval', array_values($validated['amount']));
    $billingDates = array_values($validated['billing_date']);
    $descriptions = array_values($validated['termin_description'] ?? []);
 
    $offerTotal = (float) $project->rab->grand_total;
 
    // Nominal SELAIN termin terakhir dipercaya apa adanya (itu yang benar-
    // benar diketik/dilihat user, baik lewat kolom nominal maupun hasil
    // konversi dari kolom persentase). Termin terakhir WAJIB menyerap sisa,
    // supaya totalnya selalu pas — ini juga alasan kolom nominal & persentase
    // termin terakhir dikunci (read-only) di form.
    $lastIndex    = count($amounts) - 1;
    $sumOthers    = array_sum(array_slice($amounts, 0, $lastIndex));
    $lastRemainder = $offerTotal - $sumOthers;
 
    if ($sumOthers > $offerTotal || $lastRemainder < 1) {
        return back()
            ->withErrors([
                'percentage' => 'Total nominal termin selain termin terakhir sudah '
                    . 'melebihi total penawaran. Kurangi nominal salah satu termin.',
            ])
            ->withInput();
    }
 
    $amounts[$lastIndex] = (int) round($lastRemainder);
 
    try {
        DB::transaction(function () use (
            $project,
            $currentLevel,
            $amounts,
            $billingDates,
            $descriptions,
            $offerTotal
        ) {
            // Kunci baris proyek, lalu cek ulang. Ini mencegah termin ganda
            // kalau tombol simpan terklik dua kali hampir bersamaan.
            Project::whereKey($project->id)->lockForUpdate()->first();
 
            if (BuildTermin::where('project_id', $project->id)->exists()) {
                throw new \DomainException(
                    'Setting termin sudah tersedia. Gunakan fitur edit termin.'
                );
            }
 
            foreach ($amounts as $index => $amount) {
 
                // Persentase HANYA untuk tampilan, dihitung dari nominal
                // (yang sudah pasti tepat), bukan sebaliknya.
                $percentage = $offerTotal > 0
                    ? round($amount / $offerTotal * 100, 4)
                    : 0;
 
                BuildTermin::create([
                    'project_id'   => $project->id,
                    'termin_no'    => $index + 1,
                    'percentage'   => $percentage,
                    'amount'       => $amount,
                    'billing_date' => $billingDates[$index],
                    'description'  => $descriptions[$index] ?? null,
                ]);
            }
 
            // "Setting Termin" BELUM selesai di sini — baru rencana termin
            // yang tersimpan, proses invoice & pembayarannya masih berjalan.
            // Level ini baru ditandai selesai setelah semua invoice approved
            // (lihat markSettingTerminCompletedIfAllApproved()).
            if (! $currentLevel->is_started) {
                $currentLevel->update(['is_started' => true]);
            }
        });
 
    } catch (\DomainException $e) {
 
        // Kasus yang sudah kita antisipasi (termin sudah ada).
        return back()->withErrors([
            'termin' => $e->getMessage(),
        ]);
 
    } catch (\Throwable $e) {
 
        // DB::transaction sudah rollback otomatis.
        Log::error('Gagal menyimpan setting termin', [
            'project_id' => $project->id,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);
 
        return back()
            ->withErrors([
                'termin' => 'Terjadi kesalahan saat menyimpan setting termin.',
            ])
            ->withInput();
    }
 
    return redirect()
        ->route('projects.create', ['project_id' => $project->id])
        ->with('success', 'Setting termin berhasil disimpan.');
}
 
 
// public function store(Request $request, $projectId)
// {
//     abort_if(
//         auth()->user()->cannot('ubah data proyek'),
//         403
//     );

//     $project = Project::with(['rab', 'levels'])->findOrFail($projectId);

//     $currentLevel = $project->levels->firstWhere('level_name', 'Setting Termin');

//     abort_if(! $currentLevel, 404);

//     if (! $project->rab) {
//         return back()->withErrors([
//             'termin' => 'Penawaran Harga belum tersedia.',
//         ]);
//     }

//     // Cek lebih awal: kalau termin sudah ada, tidak perlu validasi input.
//     if ($project->buildTermins()->exists()) {
//         return back()->withErrors([
//             'termin' => 'Setting termin sudah tersedia. Gunakan fitur edit termin.',
//         ]);
//     }

//     $terminCount = count((array) $request->input('percentage', []));

//     $validated = $request->validate([
//         'percentage'   => ['required', 'array', 'min:1', 'max:24'],
//         'percentage.*' => [
//             'required',
//             'numeric',
//             'min:0.01',
//             'max:100',
//             'regex:/^\d+(\.\d{1,2})?$/', // maksimal 2 desimal
//         ],

//         'termin_description'   => ['nullable', 'array', 'size:' . $terminCount],
//         'termin_description.*' => ['nullable', 'string', 'max:255'],

//         'billing_date'   => ['required', 'array', 'size:' . $terminCount],
//         'billing_date.*' => ['required', 'date'],

//         'bukti_pembayaran'   => ['nullable', 'array'],
//         'bukti_pembayaran.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB
//     ]);

//     $percentages  = array_map('floatval', array_values($validated['percentage']));
//     $billingDates = array_values($validated['billing_date']);
//     $descriptions = array_values($validated['termin_description'] ?? []);

//     if ((int) round(array_sum($percentages) * 100) !== 10000) {
//         return back()
//             ->withErrors([
//                 'percentage' => 'Total persentase termin harus tepat 100%.',
//             ])
//             ->withInput();
//     }

//     $offerTotal = (float) $project->rab->grand_total;

//     // File di-handle terpisah dari $validated karena UploadedFile
//     // perlu diproses (disimpan ke storage) satu per satu, dan di-capture
//     // secara eksplisit ke closure lewat use() di bawah.
//     $buktiPembayaranFiles = $request->file('bukti_pembayaran', []);

//     try {
//         DB::transaction(function () use (
//             $project,
//             $currentLevel,
//             $percentages,
//             $billingDates,
//             $descriptions,
//             $buktiPembayaranFiles,
//             $offerTotal
//         ) {

//             Project::whereKey($project->id)->lockForUpdate()->first();

//             if (BuildTermin::where('project_id', $project->id)->exists()) {
//                 throw new \DomainException(
//                     'Setting termin sudah tersedia. Gunakan fitur edit termin.'
//                 );
//             }

//             $lastIndex = count($percentages) - 1;
//             $allocated = 0;

//             foreach ($percentages as $index => $percentage) {

//                 $amount = $index === $lastIndex
//                     ? round($offerTotal - $allocated, 2)
//                     : round($offerTotal * ($percentage / 100));

//                 $allocated += $amount;

//                 $buktiPembayaranPath = null;

//                 if (
//                     isset($buktiPembayaranFiles[$index])
//                     && $buktiPembayaranFiles[$index] instanceof \Illuminate\Http\UploadedFile
//                 ) {
//                     $buktiPembayaranPath = $buktiPembayaranFiles[$index]->store(
//                         'build-termin/bukti-pembayaran',
//                         'public'
//                     );
//                 }

//                 BuildTermin::create([
//                     'project_id'        => $project->id,
//                     'termin_no'         => $index + 1,
//                     'percentage'        => $percentage,
//                     'amount'            => $amount,
//                     'billing_date'      => $billingDates[$index],
//                     'description'       => $descriptions[$index] ?? null,
//                     'bukti_pembayaran'  => $buktiPembayaranPath,
//                 ]);
//             }

//             $currentLevel->update(['is_completed' => true]);

//             // Tandai level berikutnya (relatif +1) sebagai mulai dikerjakan.
//             ProjectLevel::where('project_id', $project->id)
//                 ->where('level_order', $currentLevel->level_order + 1)
//                 ->update(['is_started' => true]);
//         });

//     } catch (\DomainException $e) {

//         // Kasus yang sudah kita antisipasi (termin sudah ada).
//         return back()->withErrors([
//             'termin' => $e->getMessage(),
//         ]);

//     } catch (\Throwable $e) {

//         // DB::transaction sudah rollback otomatis.
//         Log::error('Gagal menyimpan setting termin', [
//             'project_id' => $project->id,
//             'error'      => $e->getMessage(),
//             'trace'      => $e->getTraceAsString(),
//         ]);

//         return back()
//             ->withErrors([
//                 'termin' => 'Terjadi kesalahan saat menyimpan setting termin.',
//             ])
//             ->withInput();
//     }

//     return redirect()
//         ->route('projects.create', ['project_id' => $project->id])
//         ->with('success', 'Setting termin berhasil disimpan.');
// }

public function update(Request $request, $projectId)
{
    abort_if(
        auth()->user()->cannot('ubah data proyek'),
        403
    );
 
    $project = Project::with(['rab', 'levels'])->findOrFail($projectId);
 
    $currentLevel = $project->levels->firstWhere('level_name', 'Setting Termin');
 
    abort_if(! $currentLevel, 404);
 
    if (! $project->rab) {
        return back()->withErrors([
            'termin' => 'Penawaran Harga belum tersedia.',
        ]);
    }
 
    // Edit hanya untuk termin yang sudah pernah disimpan.
    // Kalau belum ada, arahkan ke simpan (store).
    if (! $project->buildTermins()->exists()) {
        return back()->withErrors([
            'termin' => 'Setting termin belum tersedia. Simpan setting termin terlebih dahulu.',
        ]);
    }
 
    // Jumlah baris termin dari input, dipakai untuk memastikan
    // semua array (nominal, tanggal, keterangan) sama panjang.
    $terminCount = count((array) $request->input('amount', []));
 
    // NOMINAL adalah sumber kebenaran, bukan persentase — supaya total
    // nominal semua termin selalu pas sama dengan grand_total penawaran,
    // tanpa sisa pembulatan. Persentase hanya dihitung ulang dari nominal
    // untuk disimpan sebagai data tampilan.
    $validated = $request->validate([
        'amount'   => ['required', 'array', 'min:1', 'max:24'],
        'amount.*' => ['required', 'integer', 'min:1'],
 
        'termin_description'   => ['nullable', 'array', 'size:' . $terminCount],
        'termin_description.*' => ['nullable', 'string', 'max:255'],
 
        'billing_date'   => ['required', 'array', 'size:' . $terminCount],
        'billing_date.*' => ['required', 'date'],
    ]);
 
    // Reindex supaya urutan 0..n-1 dan termin_no selalu berurutan.
    $amounts      = array_map('intval', array_values($validated['amount']));
    $billingDates = array_values($validated['billing_date']);
    $descriptions = array_values($validated['termin_description'] ?? []);
 
    $offerTotal = (float) $project->rab->grand_total;
 
    // Nominal selain termin terakhir dipercaya apa adanya. Termin terakhir
    // wajib menyerap sisa, supaya totalnya selalu pas — sama seperti store().
    $lastIndex     = count($amounts) - 1;
    $sumOthers     = array_sum(array_slice($amounts, 0, $lastIndex));
    $lastRemainder = $offerTotal - $sumOthers;
 
    if ($sumOthers > $offerTotal || $lastRemainder < 1) {
        return back()
            ->withErrors([
                'percentage' => 'Total nominal termin selain termin terakhir sudah '
                    . 'melebihi total penawaran. Kurangi nominal salah satu termin.',
            ])
            ->withInput();
    }
 
    $amounts[$lastIndex] = (int) round($lastRemainder);
 
    try {
        DB::transaction(function () use (
            $project,
            $amounts,
            $billingDates,
            $descriptions,
            $offerTotal
        ) {
            // Kunci baris proyek supaya dua update bersamaan tidak saling menimpa.
            Project::whereKey($project->id)->lockForUpdate()->first();
 
            // Ganti seluruh termin lama dengan yang baru.
            BuildTermin::where('project_id', $project->id)->delete();
 
            foreach ($amounts as $index => $amount) {
 
                // Persentase hanya untuk tampilan, dihitung dari nominal.
                $percentage = $offerTotal > 0
                    ? round($amount / $offerTotal * 100, 4)
                    : 0;
 
                BuildTermin::create([
                    'project_id'   => $project->id,
                    'termin_no'    => $index + 1,
                    'percentage'   => $percentage,
                    'amount'       => $amount,
                    'billing_date' => $billingDates[$index],
                    'description'  => $descriptions[$index] ?? null,
                ]);
            }
        });
 
    } catch (\Throwable $e) {
 
        // DB::transaction sudah rollback otomatis.
        Log::error('Gagal memperbarui setting termin', [
            'project_id' => $project->id,
            'error'      => $e->getMessage(),
            'trace'      => $e->getTraceAsString(),
        ]);
 
        return back()
            ->withErrors([
                'termin' => 'Terjadi kesalahan saat memperbarui setting termin.',
            ])
            ->withInput();
    }
 
    return redirect()
        ->route('projects.create', ['project_id' => $project->id])
        ->with('success', 'Setting termin berhasil diperbarui.');
}
 
}