<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keluargas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('no_kk', 20)->unique();
            $table->unsignedBigInteger('kepala_keluarga_id')->nullable(); // relasi ke warga

            // alamat lengkap (disamakan dengan warga)
            $table->string('alamat')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('jorong', 50)->nullable();

            $table->timestamps();

            // foreign key
            $table->foreign('tenant_id')
                  ->references('id')->on('tenants')
                  ->onDelete('cascade');

            $table->foreign('kepala_keluarga_id')
                  ->references('id')->on('wargas')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keluargas');
    }
};
