<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmCustomerProfile;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    public function show(Customer $customer)
    {
        $customer->load(['user', 'crmProfil']);

        $jumlahProject   = $customer->projects()->count();
        $projectTerakhir = $customer->projects()->max('start_date');

        return view('crm.customers.show', [
            'customer'        => $customer,
            'jumlahProject'   => $jumlahProject,
            'projectTerakhir' => $projectTerakhir ? Carbon::parse($projectTerakhir) : null,
            'followups'       => $customer->crmFollowups()->latest()->limit(30)->get(),
            'jadwal'          => $customer->crmJadwalReaktivasi()->orderBy('tanggal_jadwal')->get(),
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'sumber'      => ['nullable', Rule::in(array_keys(config('crm.sumber')))],
            'merk_hp'     => ['nullable', 'string', 'max:50'],
            'tipe_hp'     => ['nullable', 'string', 'max:50'],
            'profesi'     => ['nullable', 'string', 'max:100'],
            'akun_sosmed' => ['nullable', 'string', 'max:255'],
        ]);

        $profil = CrmCustomerProfile::firstOrNew(['customer_id' => $customer->id]);
        $profil->fill($data);
        $profil->pic_id = $profil->pic_id ?: $request->user()?->id;
        $profil->save();

        return back()->with('success', 'Profil CRM disimpan.');
    }
}
