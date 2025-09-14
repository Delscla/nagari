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
        Schema::create('pelayanan_attachments', function (Blueprint $table) {
            $table->id();

            // PERBAIKAN: Menambahkan kolom tenant_id yang hilang
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');

            $table->foreignId('pelayanan_request_id')->constrained('pelayanan_requests')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name')->nullable();
            $table->string('file_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pelayanan_attachments');
    }
};

