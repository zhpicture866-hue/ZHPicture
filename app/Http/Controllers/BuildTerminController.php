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
    // semua array (persentase, tanggal, keterangan) sama panjang.
    $terminCount = count((array) $request->input('percentage', []));
 
    $validated = $request->validate([
        'percentage'   => ['required', 'array', 'min:1', 'max:24'],
        'percentage.*' => [
            'required',
            'numeric',
            'min:0.01',
            'max:100',
            'regex:/^\d+(\.\d{1,2})?$/', // maksimal 2 desimal
        ],
 
        'termin_description'   => ['nullable', 'array', 'size:' . $terminCount],
        'termin_description.*' => ['nullable', 'string', 'max:255'],
 
        'billing_date'   => ['required', 'array', 'size:' . $terminCount],
        'billing_date.*' => ['required', 'date'],
    ]);
 
    // Reindex supaya urutan 0..n-1 dan termin_no selalu berurutan.
    $percentages  = array_map('floatval', array_values($validated['percentage']));
    $billingDates = array_values($validated['billing_date']);
    $descriptions = array_values($validated['termin_description'] ?? []);
 
    // Bandingkan dalam satuan 0,01% (sama seperti di JavaScript).
    if ((int) round(array_sum($percentages) * 100) !== 10000) {
        return back()
            ->withErrors([
                'percentage' => 'Total persentase termin harus tepat 100%.',
            ])
            ->withInput();
    }
 
    $offerTotal = (float) $project->rab->grand_total;
 
    try {
        DB::transaction(function () use (
            $project,
            $currentLevel,
            $percentages,
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
 
            $lastIndex = count($percentages) - 1;
            $allocated = 0;
 
            foreach ($percentages as $index => $percentage) {
 
                // Termin biasa dibulatkan ke rupiah. Termin terakhir mengambil
                // sisa, sehingga jumlah semua termin selalu sama dengan
                // grand_total penawaran.
                $amount = $index === $lastIndex
                    ? round($offerTotal - $allocated, 2)
                    : round($offerTotal * ($percentage / 100));
 
                $allocated += $amount;
 
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
    // semua array (persentase, tanggal, keterangan) sama panjang.
    $terminCount = count((array) $request->input('percentage', []));

    $validated = $request->validate([
        'percentage'   => ['required', 'array', 'min:1', 'max:24'],
        'percentage.*' => [
            'required',
            'numeric',
            'min:0.01',
            'max:100',
            'regex:/^\d+(\.\d{1,2})?$/', // maksimal 2 desimal
        ],

        'termin_description'   => ['nullable', 'array', 'size:' . $terminCount],
        'termin_description.*' => ['nullable', 'string', 'max:255'],

        'billing_date'   => ['required', 'array', 'size:' . $terminCount],
        'billing_date.*' => ['required', 'date'],
    ]);

    // Reindex supaya urutan 0..n-1 dan termin_no selalu berurutan.
    $percentages  = array_map('floatval', array_values($validated['percentage']));
    $billingDates = array_values($validated['billing_date']);
    $descriptions = array_values($validated['termin_description'] ?? []);

    // Bandingkan dalam satuan 0,01% (sama seperti di JavaScript).
    if ((int) round(array_sum($percentages) * 100) !== 10000) {
        return back()
            ->withErrors([
                'percentage' => 'Total persentase termin harus tepat 100%.',
            ])
            ->withInput();
    }

    $offerTotal = (float) $project->rab->grand_total;

    try {
        DB::transaction(function () use (
            $project,
            $percentages,
            $billingDates,
            $descriptions,
            $offerTotal
        ) {
            // Kunci baris proyek supaya dua update bersamaan tidak saling menimpa.
            Project::whereKey($project->id)->lockForUpdate()->first();

            // Ganti seluruh termin lama dengan yang baru.
            BuildTermin::where('project_id', $project->id)->delete();

            $lastIndex = count($percentages) - 1;
            $allocated = 0;

            foreach ($percentages as $index => $percentage) {
                $amount = $index === $lastIndex
                    ? round($offerTotal - $allocated, 2)
                    : round($offerTotal * ($percentage / 100));

                $allocated += $amount;

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