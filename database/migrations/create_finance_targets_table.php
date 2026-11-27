<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zhpicture.finance_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1..12
            $table->decimal('target', 18, 2)->default(0);
            $table->timestamps();
            $table->string('type', 10)->default('omset');

            $table->unique(['year', 'month', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zhpicture.finance_targets');
    }
};
