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
        // Tabel transaksi untuk setiap permintaan layanan surat
        Schema::create('pelayanan_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('pelayanan_jenis_id')->constrained('pelayanan_jenis')->onDelete('cascade');
            $table->foreignId('warga_id')->constrained('wargas')->onDelete('cascade'); // Warga yang mengajukan
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Staff yang memproses

            $table->string('nomor_surat')->nullable()->unique();
            $table->enum('status', ['Diajukan', 'Diproses', 'Selesai', 'Ditolak'])->default('Diajukan');

            // PERBAIKAN: Menggunakan nama kolom yang lebih deskriptif
            $table->text('keterangan_pemohon')->nullable(); // Catatan dari warga
            $table->text('keterangan_staff')->nullable(); // Catatan dari staff

            $table->string('file_path')->nullable(); // Path ke file PDF yang di-generate
            $table->timestamp('tanggal_selesai')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelayanan_requests');
    }
};
