<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // database/migrations/xxxx_create_users_table.php
public function up(): void
{
    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('nom');
        $table->string('telephone')->unique(); // ← remplace email
        $table->string('password');
        $table->enum('role', ['administrateur', 'responsable', 'membre'])
              ->default('membre');
        $table->boolean('actif')->default(true);
        $table->string('adresse')->nullable();
        $table->date('date_naissance')->nullable();
        $table->string('photo')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
