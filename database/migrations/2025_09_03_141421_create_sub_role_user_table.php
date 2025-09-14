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
    Schema::create('sub_role_user', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->foreignId('sub_role_id')->constrained()->onDelete('cascade');
        $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
        $table->timestamps();

        $table->unique(['user_id', 'sub_role_id', 'tenant_id']);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_role_user');
    }
};
