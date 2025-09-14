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
        Schema::create('arsip_surats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('nomor_surat', 50)->unique();
            $table->string('judul', 150);
            $table->enum('kategori', ['Surat Tugas', 'Surat Keputusan', 'Surat Undangan', 'Surat Edaran', 'Notulen', 'Lainnya']);
            $table->text('isi')->nullable();
            $table->string('file_pdf')->nullable();
            $table->string('ditandatangani_oleh', 150)->nullable();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arsip_surats');
    }
};

