<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountingPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $licenseId = config('app.license_id');

        $startYear = 2025;
        $endYear   = date('Y') + 5;

            for ($year = $startYear; $year <= $endYear; $year++) {

                DB::table('zhpicture.accounting_periods')->updateOrInsert(
                    [
                        'license_id' => $licenseId,
                        'year' => $year,
                    ],
                    [
                        'id'         => Str::uuid(),
                        'start_date' => "$year-01-01",
                        'end_date'   => "$year-12-31",
                        'is_closed'  => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
    }
}