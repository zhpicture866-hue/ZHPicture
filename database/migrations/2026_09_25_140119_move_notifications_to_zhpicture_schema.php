<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan schema zhpicture tersedia.
        DB::statement('CREATE SCHEMA IF NOT EXISTS zhpicture');

        // Hanya pindahkan jika tabel masih berada di public.
        $existsInPublic = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.tables
                WHERE table_schema = 'public'
                AND table_name = 'notifications'
            ) AS exists
        ");

        if ($existsInPublic->exists) {
            DB::statement(
                'ALTER TABLE public.notifications SET SCHEMA zhpicture'
            );
        }
    }

    public function down(): void
    {
        // Jika rollback, pindahkan kembali ke public.
        $existsInZhpicture = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.tables
                WHERE table_schema = 'zhpicture'
                AND table_name = 'notifications'
            ) AS exists
        ");

        if ($existsInZhpicture->exists) {
            DB::statement(
                'ALTER TABLE zhpicture.notifications SET SCHEMA public'
            );
        }
    }
};