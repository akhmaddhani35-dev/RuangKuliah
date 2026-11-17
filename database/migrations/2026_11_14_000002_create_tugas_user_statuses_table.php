<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tugas_user_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_id')->constrained('tugas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['Belum dikerjakan', 'Sudah dikerjakan', 'Selesai'])
                ->default('Belum dikerjakan');
            $table->timestamps();
            $table->unique(['tugas_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tugas_user_statuses');
    }
};
