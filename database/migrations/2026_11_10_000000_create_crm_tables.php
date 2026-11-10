<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 | Semua tabel mengacu ke customers.id (uuid) yang sudah ada.
 | Kolom staff_id / pic_id bertipe uuid (users.id) tanpa foreign key agar tidak bentrok dengan schema "global".
 | Bila tabel CRM harus berada di schema tertentu (mis. zhpicture), tulis nama tabel dengan prefix schema,
 | contoh: Schema::create('zhpicture.crm_interaksi', ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Data tambahan customer yang belum ada di tabel customers / users
        Schema::create('zhpicture.crm_customer_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $t->string('sumber', 50)->nullable();
            $t->string('merk_hp', 50)->nullable();
            $t->string('tipe_hp', 50)->nullable();
            $t->string('profesi', 100)->nullable();
            $t->string('akun_sosmed')->nullable();
            $t->uuid('pic_id')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('zhpicture.crm_interaksi', function (Blueprint $t) {
            $t->id();
            $t->date('tanggal');
            $t->string('platform', 30);
            $t->string('jenis', 10);                                   // ext | int
            $t->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $t->uuid('staff_id')->nullable()->index();
            $t->string('tautan')->nullable();
            $t->text('catatan')->nullable();
            $t->timestamp('diterima_at')->nullable();
            $t->timestamp('dibalas_at')->nullable();
            $t->timestamps();
        });

        Schema::create('zhpicture.crm_followups', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->uuid('staff_id')->nullable()->index();
            $t->string('kanal', 20);                                   // wa | telepon | meeting | offline
            $t->text('catatan');
            $t->date('jadwal_berikutnya')->nullable()->index();
            $t->string('status', 20)->default('menunggu');             // menunggu | selesai
            $t->timestamps();
        });

        Schema::create('zhpicture.crm_feedbacks', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->date('tanggal');
            $t->string('jenis', 20);                                   // saran | komplain | survey | review
            $t->text('isi')->nullable();
            $t->unsignedTinyInteger('rating')->nullable();
            $t->string('platform_review', 50)->nullable();
            $t->string('status_penanganan', 20)->default('baru');
            $t->uuid('ditangani_oleh')->nullable();
            $t->timestamps();
        });

        Schema::create('zhpicture.crm_jadwal_reaktivasi', function (Blueprint $t) {
            $t->id();
            $t->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $t->string('interval', 20);                                // H+2 | H+30 | H+90 | H+1 tahun
            $t->date('tanggal_jadwal')->index();
            $t->string('status', 20)->default('menunggu');             // menunggu | selesai
            $t->foreignId('followup_id')->nullable()->constrained('zhpicture.crm_followups')->nullOnDelete();
            $t->timestamps();
            $t->unique(['customer_id', 'interval', 'tanggal_jadwal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zhpicture.crm_jadwal_reaktivasi');
        Schema::dropIfExists('zhpicture.crm_feedbacks');
        Schema::dropIfExists('zhpicture.crm_followups');
        Schema::dropIfExists('zhpicture.crm_interaksi');
        Schema::dropIfExists('zhpicture.crm_customer_profiles');
    }
};
