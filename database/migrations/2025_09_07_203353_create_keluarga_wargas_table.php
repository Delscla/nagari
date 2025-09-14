<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keluarga_warga', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('keluarga_id');
            $table->unsignedBigInteger('warga_id');
            $table->string('hubungan')->nullable(); // contoh: Kepala Keluarga, Istri, Anak, dll
            $table->timestamps();

            $table->foreign('keluarga_id')->references('id')->on('keluargas')->onDelete('cascade');
            $table->foreign('warga_id')->references('id')->on('wargas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keluarga_warga');
    }
};
