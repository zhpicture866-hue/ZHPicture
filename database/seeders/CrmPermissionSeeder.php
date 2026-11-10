<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CrmPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $izin = ['lihat data crm', 'kelola data crm'];
        foreach ($izin as $nama) {
            Permission::firstOrCreate(['name' => $nama, 'guard_name' => 'web']);
        }

        // Beri ke role Super-Admin bila ada; role lain atur lewat menu role/permission yang sudah ada.
        Role::where('name', 'Super-Admin')->first()?->givePermissionTo($izin);
    }
}
