<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zhpicture.invoices', function (Blueprint $table) {
            $table->unsignedInteger('termin_no')->default(1)->after('invoice_type');
            $table->string('termin_label')->nullable()->after('termin_no'); // "DP", "Pelunasan", "Termin 1", dst
        });
    }

    public function down(): void
    {
        Schema::table('zhpicture.invoices', function (Blueprint $table) {
            $table->dropColumn(['termin_no', 'termin_label']);
        });
    }
};