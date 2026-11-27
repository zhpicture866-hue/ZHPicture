<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('zhpicture.projects', function (Blueprint $table) {
            $table->timestamps();
        });

        DB::table('zhpicture.projects')->update([
            'created_at' => DB::raw('start_date'),
            'updated_at' => DB::raw('start_date'),
        ]);
    }

    public function down(): void
    {
        Schema::table('zhpicture.projects', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
