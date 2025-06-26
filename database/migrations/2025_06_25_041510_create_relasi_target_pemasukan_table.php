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
        Schema::create('relasi_target_pemasukan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_target')->constrained('target')->onDelete('cascade');
            $table->foreignId('id_pemasukan')->constrained('pemasukan')->onDelete('cascade');
            $table->decimal('jumlah_alokasi', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relasi_target_pemasukan');
    }
};
