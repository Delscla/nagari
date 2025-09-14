<!-- 2025_09_10_063544_create_surat_templates_table.php -->
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
        Schema::create('surat_templates', function (Blueprint $table) {
            $table->id();
            // Setiap template dimiliki oleh satu tenant
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('nama_surat');
            $table->string('deskripsi')->nullable();
            // Kolom ini akan menyimpan konten HTML dari WYSIWYG editor
            $table->longText('konten');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surat_templates');
    }
};

