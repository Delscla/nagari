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
        Schema::create('wargas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // 🔐 Encrypted data
            $table->text('nik')->nullable();
            $table->string('nik_hash', 64)->unique();

            $table->text('no_kk')->nullable();
            $table->string('no_kk_hash', 64)->nullable()->index(); // <-- tambahkan kolom hash
            $table->string('nama', 150);
            $table->string('tempat_lahir', 100);
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->enum('status_perkawinan', ['Belum Kawin','Kawin','Cerai Hidup','Cerai Mati']);
            $table->string('pendidikan', 100)->nullable();
            $table->string('pekerjaan', 100)->nullable();
            $table->string('agama', 50)->nullable();
            $table->text('alamat')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('jorong', 100)->nullable();
            $table->enum('status_domisili', ['Tetap','Pendatang','Pindah','Meninggal']);
            $table->string('no_hp', 20)->nullable();
            $table->string('email', 100)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wargas');
    }
};
