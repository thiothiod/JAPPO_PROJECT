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
    Schema::create('audit_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('utilisateur_id')
              ->nullable()
              ->constrained('users')
              ->nullOnDelete();
        $table->string('action');        // ex: cotisation.create
        $table->string('entite');        // ex: cotisations
        $table->unsignedBigInteger('entite_id')->nullable();
        $table->json('donnees_avant')->nullable();
        $table->json('donnees_apres')->nullable();
        $table->string('ip')->nullable();
        $table->timestamp('created_at'); // pas de updated_at — append-only
    });
}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
