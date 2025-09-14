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
        Schema::create('pelayanan_jenis', function (Blueprint $table) {
            $table->id();

            // Menambahkan foreign key untuk tenant
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->string('kategori');
            $table->string('nama');
            $table->json('syarat')->nullable();

            // Menambahkan foreign key untuk template surat
            $table->foreignId('surat_template_id')
                  ->nullable()
                  ->constrained('surat_templates') // Terhubung ke tabel surat_templates
                  ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelayanan_jenis');
    }
};

