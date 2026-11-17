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
        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('id_mata_kuliah')->constrained('mata_kuliahs');
            $table->string('judul');
            $table->string('nama_mata_kuliah');
            $table->text('deskripsi');
            $table->date('deadline');
            $table->enum('prioritas', ['Tinggi', 'Sedang', 'Rendah']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tugas');
    }
};
