<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_xxxxxx_create_letterhead_settings_table.php
public function up(): void
{
    Schema::create('letterhead_settings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
        $table->string('logo_path')->nullable();
        $table->string('line1')->default('PEMERINTAH KABUPATEN SIJUNJUNG');
        $table->string('line2')->default('KECAMATAN SIJUNJUNG • NAGARI SIJUNJUNG');
        $table->string('line3')->default('Jl. Raya Nagari No. 123, Kecamatan Sijunjung, Kabupaten Sijunjung, Sumatera Barat');
        $table->string('line4')->default('Telp: (0751) 123456 • Email: info@nagaridigital.id');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letterhead_settings');
    }
};
