<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'name'], 'users_role_name_idx');
        });

        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->index(['user_id', 'nama'], 'mata_kuliahs_user_nama_idx');
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->index(['user_id', 'hari', 'waktu_mulai'], 'jadwals_user_hari_mulai_idx');
        });

        Schema::table('materis', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'materis_user_created_at_idx');
            $table->index(['id_mata_kuliah', 'created_at'], 'materis_course_created_at_idx');
        });

        Schema::table('tugas', function (Blueprint $table) {
            $table->index(['user_id', 'deadline'], 'tugas_user_deadline_idx');
            $table->index(['id_mata_kuliah', 'deadline'], 'tugas_course_deadline_idx');
        });

        Schema::table('agenda_ujians', function (Blueprint $table) {
            $table->index(['user_id', 'tanggal', 'waktu_mulai'], 'agenda_user_tanggal_mulai_idx');
        });

        Schema::table('tugas_user_statuses', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'tugas_id'], 'tugas_status_user_status_task_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tugas_user_statuses', function (Blueprint $table) {
            $table->dropIndex('tugas_status_user_status_task_idx');
        });

        Schema::table('agenda_ujians', function (Blueprint $table) {
            $table->dropIndex('agenda_user_tanggal_mulai_idx');
        });

        Schema::table('tugas', function (Blueprint $table) {
            $table->dropIndex('tugas_course_deadline_idx');
            $table->dropIndex('tugas_user_deadline_idx');
        });

        Schema::table('materis', function (Blueprint $table) {
            $table->dropIndex('materis_course_created_at_idx');
            $table->dropIndex('materis_user_created_at_idx');
        });

        Schema::table('jadwals', function (Blueprint $table) {
            $table->dropIndex('jadwals_user_hari_mulai_idx');
        });

        Schema::table('mata_kuliahs', function (Blueprint $table) {
            $table->dropIndex('mata_kuliahs_user_nama_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_name_idx');
        });
    }
};
