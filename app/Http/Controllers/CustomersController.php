<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Religion;
use App\Models\Customer;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\SubDistrict;
use App\Models\PostalCode;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Bank;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class CustomersController extends Controller
{

public function index(Request $request)
{
    $auth = auth()->user();
 
    // Query dasar (sudah dibatasi hak akses). Dipakai untuk tabel DAN kartu statistik.
    $baseQuery = Customer::with(['user.roles']);
 
    if ($auth->can('lihat data customer') && !$auth->can('lihat daftar customer')) {
        $baseQuery->where('user_id', $auth->id);
    }
 
    if ($request->ajax()) {
        // withCount hanya di query tabel (bukan di $baseQuery) supaya perhitungan kartu tidak terganggu
        $query = (clone $baseQuery)->withCount('projects');
 
        // Filter tab (Lead, Prospek, New Customer, Silver, Gold, Platinum)
        if ($request->filled('loyalty')) {
            $query->where('loyalty_level', $request->loyalty);
        }
 
        $customers = $query->get();
 
        return DataTables::of($customers)
            ->addIndexColumn()
            ->addColumn('customer', function ($row) {
                $name     = Str::title($row->user->fullname ?? '-');
                $email    = $row->user->email ?? '-';
                $initials = collect(explode(' ', trim($name)))
                    ->filter()->take(2)
                    ->map(fn($w) => mb_substr($w, 0, 1))
                    ->implode('');
                $url = route('customers.show', $row->id);
 
                return '<div class="d-flex align-items-center">
                            <span class="cust-avatar av-' . (crc32($name) % 5) . '">' . e(strtoupper($initials)) . '</span>
                            <div class="ms-3">
                                <a href="' . $url . '" class="cust-name">' . e($name) . '</a>
                                <div class="cust-email">' . e($email) . '</div>
                            </div>
                        </div>';
            })
            ->editColumn('channel', fn($row) => $row->readable_channel)
            ->addColumn('category', fn($row) => $this->readableCategory($row->category))
            ->addColumn('orders', fn($row) => (int) $row->projects_count)
            ->addColumn('loyalty', function ($row) {
                // NOTE: pakai angka loyalty_level untuk warna (bukan teks),
                // karena sebelumnya match('1','2',...) tidak pernah cocok dengan teks "Lead", dst.
                $level = (int) $row->loyalty_level;
                return '<span class="lv-badge lv-' . $level . '">' . e($row->readable_loyalty_level) . '</span>';
            })
            ->addColumn('action', function ($customer) {
                $buttons = '<div class="d-flex justify-content-end gap-1">';
                if (auth()->user()->can('ubah data customer')) {
                    $buttons .= '<a href="' . route('customers.edit', $customer->id) . '" class="btn btn-icon btn-sm btn-ghost-dark" title="Ubah"><i class="ti ti-edit"></i></a>';
                }
                if (auth()->user()->can('lihat data customer')) {
                    $buttons .= '<a href="' . route('customers.show', $customer->id) . '" class="btn btn-icon btn-sm btn-ghost-dark" title="Lihat"><i class="ti ti-eye"></i></a>';
                }
                // if (auth()->user()->can('lihat data crm')) {
                //     $buttons .= '<a href="' . route('crm.customers.show', $customer->id) . '" class="btn btn-icon btn-sm btn-ghost-dark" title="CRM"><i class="ti ti-address-book"></i></a>';
                // }
                if (auth()->user()->can('hapus data customer')) {
                    $buttons .= '<button data-id="' . $customer->id . '" class="btn btn-icon btn-sm btn-ghost-danger delete-customer" title="Hapus"><i class="ti ti-trash"></i></button>';
                }
                return $buttons . '</div>';
            })
            ->rawColumns(['customer', 'loyalty', 'action'])
            ->with('stats', $this->loyaltyStats($baseQuery)) // supaya kartu ikut ter-update setelah hapus
            ->make(true);
    }
 
    return view('customers.index', [
        'stats' => $this->loyaltyStats($baseQuery),
    ]);
}
private function loyaltyStats($baseQuery): array
{
    $counts = (clone $baseQuery)
        ->toBase()
        ->reorder()
        ->selectRaw('loyalty_level, COUNT(*) as total')
        ->groupBy('loyalty_level')
        ->pluck('total', 'loyalty_level');
 
    $stats = [];
    foreach (range(1, 6) as $level) {
        $stats[$level] = (int) ($counts[$level] ?? 0);
    }
 
    return $stats;
}
    private function readableCategory($value)
    {
        return match ((int) $value) {
            1 => 'Individu',
            2 => 'Perusahaan',
            3 => 'Instansi',
            4 => 'Lainnya',
            default => '-',
        };
    }

    private function readableLoyaltyLevel($value)
    {
        return match ((int) $value) {
            1 => 'Lead',
            2 => 'Prospek',
            3 => 'New Customer',
            4 => 'Silver',
            5 => 'Gold',
            6 => 'Platinum',
            default => '-',
        };
    }

    public function create(Customer $customer)
    {
        $user = auth()->user();
        $religions = Religion::all();
        $provinces = Province::all();
        $roles = Role::all();
        return view('customers.create', compact('customer', 'user', 'roles', 'religions', 'provinces'));
    }

    public function store(Request $request)
{
    $code = preg_replace('/\D/', '', $request->phone_code ?? '');
    $num  = ltrim(preg_replace('/\D/', '', $request->phone_number ?? ''), '0');

    if ($code === '62' && str_starts_with($num, '62')) {
        $num = substr($num, 2);
    }

    $request->merge(['phone' => $num !== '' ? $code . $num : null]);
    $validated = $request->validate([
        'user_id' => ['nullable','uuid', Rule::exists(User::class,'id')],
        'fullname' => 'required|string|max:255',
        'nickname' => 'nullable|string|max:100',
        'gender' => 'nullable|in:1,2',
        'title' => 'nullable|in:1,2,3',
        'email' => [
            'required',
            'email',
            Rule::unique(User::class, 'email'),
        ],
        'birth_place' => 'nullable|string|max:100',
        'birth_date' => 'nullable|date_format:Y-m-d',
        'identity_number' => [
            'nullable',
            'regex:/^[0-9]{16}$/',
            Rule::unique(User::class, 'identity_number'),
        ],
        'religion_id' => [
            'nullable',
            Rule::exists(Religion::class, 'id'),
        ],
        'npwp' => 'nullable|string|max:30',
        'phone_code' => ['nullable', 'required_with:phone_number', Rule::in(array_keys(config('phone_codes')))],
        'phone'      => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'],
        'address' => 'nullable|string|max:255',
        'user_province_id' => [
            'nullable',
            Rule::exists(Province::class, 'id'),
        ],
        'user_city_id' => ['nullable', Rule::exists(City::class, 'id')],
        'user_district_id' => ['nullable', Rule::exists(District::class, 'id')],
        'user_sub_district_id' => ['nullable', Rule::exists(SubDistrict::class, 'id')],
        'user_postal_code_id' => ['nullable', Rule::exists(PostalCode::class, 'id')],
        'bank_id' => ['nullable','uuid', Rule::exists(Bank::class,'id')],
        'account_number' => 'nullable|string|max:50',
        'account_holder' => 'nullable|string|max:50',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

        // --- data Customer ---
        'nic' => 'required|unique:customers,nic',
        'shipping_name' => 'nullable|string|max:255',
        'shipping_phone' => 'nullable|string|max:20',
        'shipping_address' => 'nullable|string|max:255',
        'province_id' => [
            'nullable',
            Rule::exists(Province::class, 'id'),
        ],
        'city_id' => ['nullable', Rule::exists(City::class, 'id')],
        'district_id' => ['nullable', Rule::exists(District::class, 'id')],
        'sub_district_id' => ['nullable', Rule::exists(SubDistrict::class, 'id')],
        'postal_code_id' => ['nullable', Rule::exists(PostalCode::class, 'id')],
        'role' => 'nullable|array',
        'role.*' => 'string|exists:roles,name',
        'category' => 'nullable|in:1,2,3,4',
        'channel' => ['nullable', Rule::in(array_keys(Customer::CHANNELS))],
        'loyalty_level' => 'nullable|in:1,2,3,4,5,6'
    ]);

    if ($request->hasFile('photo')) {
        $filename = Str::uuid().'.'.$request->file('photo')->getClientOriginalExtension();

        $path = $request->file('photo')->storeAs(
            'photos',
            $filename,
            'public'
        );

        // simpan full relative path
        $validated['photo'] = $path;   // → photos/uuid.jpg
    }

    // Jika alamat pengiriman sama dengan domisili user
        if ($request->has('same_address')) {
            $validated['province_id'] = $validated['user_province_id'];
            $validated['city_id'] = $validated['user_city_id'];
            $validated['district_id'] = $validated['user_district_id'];
            $validated['sub_district_id'] = $validated['user_sub_district_id'];
            $validated['postal_code_id'] = $validated['user_postal_code_id'];
            $validated['shipping_address'] = $validated['address'];
            $validated['shipping_name'] = $validated['fullname'];
            $validated['shipping_phone'] = $validated['phone'];
        }

    DB::transaction(function () use ($validated) {

        if (!empty($validated['user_id'])) {
            $user = User::find($validated['user_id']);
        } else {
            $password = '12345678';
            $user = User::create([
                'id' => Str::uuid(),
                'fullname' => $validated['fullname'],
                'nickname' => $validated['nickname'],
                'birth_place' => $validated['birth_place'],
                'birth_date' => $validated['birth_date'],
                'identity_number' => $validated['identity_number'],
                'npwp' => $validated['npwp'],
                'address' => $validated['address'],
                'religion_id' => $validated['religion_id'],
                'province_id' => $validated['user_province_id'],
                'city_id' => $validated['user_city_id'],
                'district_id' => $validated['user_district_id'],
                'sub_district_id' => $validated['user_sub_district_id'],
                'postal_code_id' => $validated['user_postal_code_id'],
                'email' => $validated['email'],
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'phone' => $validated['phone'] ?? null,
                'phone_code' => $validated['phone_code'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'title' => $validated['title'] ?? null,
                'photo' => $validated['photo'] ?? null,
                'bank_id' => $validated['bank_id'] ?? null,
                'account_number' => $validated['account_number'] ?? null,
                'account_holder' => $validated['account_holder'] ?? null,
            ]);

            session()->flash('new_user_password', $password);
        }

        if (!empty($validated['role'])) {
            foreach ($validated['role'] as $r) {
                if (!$user->hasRole($r)) {
                    $user->assignRole($r);
                }
            }
        }

        Customer::create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'nic' => $validated['nic'],
            'shipping_name' => $validated['shipping_name'],
            'shipping_phone' => $validated['shipping_phone'],
            'shipping_address' => $validated['shipping_address'],
            'province_id' => $validated['province_id'],
            'city_id' => $validated['city_id'],
            'district_id' => $validated['district_id'],
            'sub_district_id' => $validated['sub_district_id'],
            'postal_code_id' => $validated['postal_code_id'],
            'category' => $validated['category'] ?? null,
            'channel' => $validated['channel'] ?? null,
            'loyalty_level' => !empty($validated['loyalty_level'])
                ? (int) $validated['loyalty_level']
                : 1,
            'photo' => $validated['photo'] ?? null,
        ]);
    });

    return redirect()
        ->route('customers.index')
        ->with('success', 'Data customer berhasil ditambahkan.' .
            (session('new_user_password') ? ' Akun user baru dibuat. Password: ' . session('new_user_password') : '')
        );
}

public static function generateNicAjax()
{
    $lastNumber = Customer::where('nic', 'like', 'C-%')
        ->selectRaw("
            MAX(
                CAST(
                    REGEXP_REPLACE(nic, '[^0-9]', '', 'g')
                    AS INTEGER
                )
            ) as max_nic
        ")
        ->value('max_nic');

    $newNumber = ($lastNumber ?? 0) + 1;
    $nic = 'C-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);

    return response()->json([
        'nic' => $nic
    ]);
}

 public function show(Customer $customer)
    {
        $customer->load('user');
        return view('sdm.customers.show', [
            'user' => $customer->user,
            'customer' => $customer
        ]);
    }

public function edit($id)
    {
        $customer = Customer::with(['user.roles', 'user.bank'])->findOrFail($id);
        $user = $customer->user;
        $religions = Religion::all();
        $provinces = Province::all();
        $roles = Role::all();
        $selectedRoles = $user->roles->pluck('name')->toArray();
        $cities = City::where('province_id', $user->province_id)->get();
        $districts = District::where('city_id', $user->city_id)->get();
        $subDistricts = SubDistrict::where('district_id', $user->district_id)->get();
        $postalCodes = PostalCode::where('sub_district_id', $user->sub_district_id)->get();
        

        return view('customers.edit', compact('user', 'roles', 'selectedRoles', 'customer',
        'religions', 'provinces', 'cities', 'districts', 'subDistricts', 'postalCodes'));
    }

public function update(Request $request, Customer $customer)
{
    $code = preg_replace('/\D/', '', $request->phone_code ?? '');
    $num  = ltrim(preg_replace('/\D/', '', $request->phone_number ?? ''), '0');

    if ($code === '62' && str_starts_with($num, '62')) {
        $num = substr($num, 2);
    }

    $request->merge(['phone' => $num !== '' ? $code . $num : null]);
    $validated = $request->validate([
        // --- data user ---
        'fullname' => 'required|string|max:255',
        'nickname' => 'nullable|string|max:100',
        'gender' => 'nullable|in:1,2',
        'title' => 'nullable|in:1,2,3',
        'email' => [
            'required',
            'email',
            Rule::unique(User::class, 'email')
                ->ignore($customer->user_id),
        ],
        'birth_place' => 'nullable|string|max:100',
        'birth_date' => 'nullable|date_format:Y-m-d',
        'identity_number' => [
            'nullable',
            'regex:/^[0-9]{16}$/',
            Rule::unique(User::class, 'identity_number')
                ->ignore($customer->user_id),
        ],
        'religion_id' => [
            'nullable',
            Rule::exists(Religion::class, 'id'),
        ],
        'npwp' => 'nullable|string|max:30',
        'phone_code' => ['nullable', 'required_with:phone_number', Rule::in(array_keys(config('phone_codes')))],
        'phone'      => ['nullable', 'regex:/^[1-9][0-9]{7,14}$/'],
        'address' => 'nullable|string|max:255',
        'user_province_id' => [
            'nullable',
            Rule::exists(Province::class, 'id'),
        ],
        'user_city_id' => ['nullable', Rule::exists(City::class, 'id')],
        'user_district_id' => ['nullable', Rule::exists(District::class, 'id')],
        'user_sub_district_id' => ['nullable', Rule::exists(SubDistrict::class, 'id')],
        'user_postal_code_id' => ['nullable', Rule::exists(PostalCode::class, 'id')],
        'bank_id' => ['nullable','uuid', Rule::exists(Bank::class,'id')],
        'account_number' => 'nullable|string|max:50',
        'account_holder' => 'nullable|string|max:50',
        'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

        'nic' => 'nullable|string|max:50|unique:customers,nic,' . $customer->id,
        'shipping_name' => 'nullable|string|max:255',
        'shipping_phone' => 'nullable|string|max:20',
        'shipping_address' => 'nullable|string|max:255',
        'province_id' => [
            'nullable',
            Rule::exists(Province::class, 'id'),
        ],
        'city_id' => [
            'nullable',
            Rule::exists(City::class, 'id'),
        ],
        'district_id' => [
            'nullable',
            Rule::exists(District::class, 'id'),
        ],

        'sub_district_id' => [
            'nullable',
            Rule::exists(SubDistrict::class, 'id'),
        ],

        'postal_code_id' => [
            'nullable',
            Rule::exists(PostalCode::class, 'id'),
        ],
        'category' => 'nullable|in:1,2,3,4',
        'loyalty_level' => 'nullable|in:1,2,3,4,5,6',
        'channel' => ['nullable', Rule::in(array_keys(Customer::CHANNELS))],
        'role' => 'nullable|array',
        'role.*' => 'string|exists:roles,name',
    ]);

    if ($request->has('same_address')) {
            $validated['province_id'] = $validated['user_province_id'];
            $validated['city_id'] = $validated['user_city_id'];
            $validated['district_id'] = $validated['user_district_id'];
            $validated['sub_district_id'] = $validated['user_sub_district_id'];
            $validated['postal_code_id'] = $validated['user_postal_code_id'];
            $validated['shipping_address'] = $validated['address'];
            $validated['shipping_name'] = $validated['fullname'];
            $validated['shipping_phone'] = $validated['phone'];
        }

    $newPhotoPath = null;

    DB::transaction(function () use ($validated, $customer, $request) {
        $user = $customer->user;

        if ($request->hasFile('photo')) {
            $newPhotoPath = $request->file('photo')->storeAs(
                'photos',
                Str::uuid().'.'.$request->file('photo')->getClientOriginalExtension(),
                'public'
            );

            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            $validated['photo'] = $newPhotoPath;
        }

        $user->update([
            'fullname' => $validated['fullname'],
            'nickname' => $validated['nickname'],
            'birth_place' => $validated['birth_place'],
            'birth_date' => $validated['birth_date'],
            'identity_number' => $validated['identity_number'],
            'religion_id' => $validated['religion_id'],
            'npwp' => $validated['npwp'],
            'address' => $validated['address'],
            'province_id' => $validated['user_province_id'],
            'city_id' => $validated['user_city_id'],
            'district_id' => $validated['user_district_id'],
            'sub_district_id' => $validated['user_sub_district_id'],
            'postal_code_id' => $validated['user_postal_code_id'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'phone_code' => $validated['phone_code'],
            'gender' => $validated['gender'],
            'title' => $validated['title'],
            'bank_id' => $validated['bank_id'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'account_holder' => $validated['account_holder'] ?? null,
            'photo' => $validated['photo'] ?? $user->photo,
        ]);

        if (!empty($validated['role'])) {
            foreach ($validated['role'] as $r) {
                if (!$user->hasRole($r)) {
                    $user->assignRole($r);
                }
            }
        } else {
            // Pastikan minimal masih punya role Customer
            if (!$user->hasRole('Customer')) {
                $user->assignRole('Customer');
            }
        }

        $customer->update([
            'nic' => $validated['nic'],
            'shipping_name' => $validated['shipping_name'],
            'shipping_phone' => $validated['shipping_phone'],
            'shipping_address' => $validated['shipping_address'],
            'province_id' => $validated['province_id'],
            'city_id' => $validated['city_id'],
            'district_id' => $validated['district_id'],
            'sub_district_id' => $validated['sub_district_id'],
            'postal_code_id' => $validated['postal_code_id'],
            'category' => $validated['category'] ?? null,
            'channel' => $validated['channel'] ?? null,
            'loyalty_level' => !empty($validated['loyalty_level'])
                ? (int) $validated['loyalty_level']
                : 1,
            'photo' => $validated['photo'] ?? $customer->photo,
        ]);
    });

    return redirect()
        ->route('customers.show', $customer->id)
        ->with('success', 'Data customer berhasil diperbarui.');
}


public function destroy(Customer $customer): JsonResponse
{
    DB::transaction(function () use ($customer) {
        $user = $customer->user;

        // 🔹 Hapus foto customer dari storage kalau ada
        if ($customer->photo && Storage::disk('public')->exists('photos/' . $customer->photo)) {
            Storage::disk('public')->delete('photos/' . $customer->photo);
        }

        // 🔹 Hapus record customer
        $customer->delete();

        // 🔹 Cek user yang terhubung
        if ($user) {
            $roles = $user->roles->pluck('name')->toArray();

            // Kalau hanya punya role "Customer"
            if (count($roles) === 1 && in_array('Customer', $roles)) {

                // Hapus juga foto user kalau ada
                if ($user->photo && Storage::disk('public')->exists('photos/' . $user->photo)) {
                    Storage::disk('public')->delete('photos/' . $user->photo);
                }

                // Hapus akun user
                $user->delete();

            } else {
                // Kalau user masih punya role lain, hapus hanya role Customer-nya
                $user->removeRole('Customer');
            }
        }
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Data customer berhasil dihapus.'
    ]);
}



}
