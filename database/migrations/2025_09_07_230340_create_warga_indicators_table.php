<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('warga_indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('warga_id')->constrained('wargas')->cascadeOnDelete();
            $table->boolean('miskin')->default(false);
            $table->boolean('disabilitas')->default(false);
            $table->boolean('lansia')->default(false);
            $table->boolean('yatim_piatu')->default(false);
            $table->enum('status_bantuan', ['PKH','BLT','BPNT','KIS','Tidak Ada'])->nullable();
            $table->text('keterangan')->nullable();

            // Tambahan kolom baru
            $table->decimal('penghasilan', 15, 2)->nullable(); // penghasilan per bulan
            $table->string('sumber_penghasilan', 150)->nullable(); // sumber penghasilan
            $table->enum('status_kemiskinan', ['Miskin','Rentan Miskin','Tidak Miskin'])->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warga_indicators');
    }
};
