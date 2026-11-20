<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zhpicture.crm_templates', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->text('isi');                       // placeholder: {nama}, {panggilan}, {sapaan}
            $t->boolean('aktif')->default(true);
            $t->uuid('dibuat_oleh')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_templates');
    }
};
