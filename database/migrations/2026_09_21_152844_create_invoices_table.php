<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zhpicture.invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('project_id');
            $table->foreign('project_id')
                ->references('id')
                ->on('zhpicture.projects')
                ->cascadeOnDelete();

            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->string('invoice_type'); // survey|dp|final|rab|build|wedding — lihat catatan constant di model
            $table->decimal('amount', 15, 2);

            $table->string('status')->default('draft'); // draft|waiting_approval|approved|rejected|paid

            $table->timestamp('approved_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->string('approval_token')->nullable();

            $table->timestamp('rejected_at')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->text('reject_note')->nullable();

            $table->timestamp('invoice_dp_downloaded_at')->nullable();
            $table->timestamp('invoice_dp_approved_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();

            $table->string('approve_by_name')->nullable();
            $table->string('approved_ip')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zhpicture.invoices');
    }
};