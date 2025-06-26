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
         Schema::create('target', function (Blueprint $table) {
            $table->id();
            $table->string('nama_target');
            $table->decimal('target_dana', 15, 2);
            $table->decimal('terkumpul', 15, 2)->default(0);
            $table->date('target_tanggal');
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['aktif', 'tercapai', 'tidak_tercapai'])->default('aktif');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('kategori_target_id')->constrained('kategori_target')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target');
    }
};
