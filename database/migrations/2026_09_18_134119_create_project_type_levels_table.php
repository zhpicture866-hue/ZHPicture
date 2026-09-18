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
        Schema::create('zhpicture.project_type_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_type_id')
                ->constrained('zhpicture.project_types')
                ->cascadeOnDelete();
            $table->unsignedInteger('level_order');
            $table->string('level_name');
            $table->timestamps();

            $table->unique(['project_type_id', 'level_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zhpicture.project_type_levels');
    }
};
