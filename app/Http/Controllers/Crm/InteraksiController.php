<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmInteraksi;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class InteraksiController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = CrmInteraksi::with('customer.user')
                ->when($request->boolean('belum_dibalas'), fn ($q) => $q->where('jenis', 'int')->whereNull('dibalas_at'));

            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('tanggal', fn ($r) => $r->tanggal->format('d/m/Y'))
                ->editColumn('platform', fn ($r) => e(config('crm.platform')[$r->platform] ?? $r->platform))
                ->editColumn('jenis', fn ($r) => $r->jenis === 'int' ? 'Internal' : 'Eksternal')
                ->addColumn('customer', function ($r) {
                    if (! $r->customer) {
                        return '-';
                    }
                    $nama = e($r->customer->user?->fullname ?? $r->customer->nic);

                    return '<a href="' . route('crm.customers.show', $r->customer) . '">' . $nama . '</a>';
                })
                ->editColumn('catatan', fn ($r) => e($r->catatan ?: '-'))
                ->addColumn('status', function ($r) {
                    if ($r->jenis !== 'int') {
                        return '-';
                    }

                    return $r->dibalas_at
                        ? '<span class="badge bg-green-lt">Dibalas ' . $r->dibalas_at->format('d/m H:i') . '</span>'
                        : '<span class="badge bg-red-lt">Belum dibalas</span>';
                })
                ->addColumn('action', function ($r) {
                    if ($r->jenis !== 'int' || $r->dibalas_at || ! auth()->user()?->can('kelola data crm')) {
                        return '';
                    }

                    return '<button type="button" class="btn btn-sm btn-dark btn-dibalas" data-id="' . $r->id . '">Tandai dibalas</button>';
                })
                ->rawColumns(['customer', 'status', 'action'])
                ->make(true);
        }

        return view('crm.interaksi.index', [
            'platform'     => config('crm.platform'),
            'belumDibalas' => CrmInteraksi::where('jenis', 'int')->whereNull('dibalas_at')->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal'     => ['required', 'date'],
            'platform'    => ['required', Rule::in(array_keys(config('crm.platform')))],
            'jenis'       => ['required', Rule::in(['ext', 'int'])],
            'no_whatsapp' => ['nullable', 'string', 'max:20'],
            'tautan'      => ['nullable', 'string', 'max:255'],
            'catatan'     => ['nullable', 'string'],
        ]);

        $customerId = null;
        $nomor = preg_replace('/\D+/', '', (string) ($data['no_whatsapp'] ?? ''));
        unset($data['no_whatsapp']);

        if ($nomor !== '') {
            $nomor = str_starts_with($nomor, '0') ? '62' . substr($nomor, 1) : $nomor;
            $customerId = Customer::whereHas('user', fn ($q) => $q->where('phone', 'like', '%' . substr($nomor, 2)))->value('id');
        }

        CrmInteraksi::create($data + [
            'customer_id' => $customerId,
            'staff_id'    => $request->user()?->id,
            // Interaksi internal = chat/komentar masuk yang harus dibalas
            'diterima_at' => $data['jenis'] === 'int' ? now() : null,
        ]);

        return back()->with('success', 'Interaksi dicatat.');
    }

    public function dibalas(CrmInteraksi $interaksi)
    {
        $interaksi->update(['dibalas_at' => now()]);

        return response()->json(['status' => 'success']);
    }
}
