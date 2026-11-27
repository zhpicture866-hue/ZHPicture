<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // data target lama otomatis menjadi target 'omset'
        Schema::table('zhpicture.finance_targets', function (Blueprint $t) {
            $t->string('type', 10)->default('omset');
        });

        // unik lama (year, month) diganti (year, type, month)
        DB::statement('ALTER TABLE zhpicture.finance_targets DROP CONSTRAINT IF EXISTS finance_targets_year_month_unique');
        DB::statement('DROP INDEX IF EXISTS finance_targets_year_month_unique');

        Schema::table('zhpicture.finance_targets', function (Blueprint $t) {
            $t->unique(['year', 'type', 'month']);
        });
    }

    public function down(): void
    {
        Schema::table('zhpicture.finance_targets', function (Blueprint $t) {
            $t->dropUnique(['year', 'type', 'month']);
            $t->dropColumn('type');
        });
    }
};
