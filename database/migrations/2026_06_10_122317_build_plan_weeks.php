<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // decimal(8,4): cukup untuk 0.0001% - 100.0000%.
        DB::statement(
            'ALTER TABLE zhpicture.build_termins ALTER COLUMN percentage TYPE decimal(8,4)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'ALTER TABLE zhpicture.build_termins ALTER COLUMN percentage TYPE decimal(5,2)'
        );
    }
};