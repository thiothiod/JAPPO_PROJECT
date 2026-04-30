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
    Schema::create('groupement_membres', function (Blueprint $table) {
        $table->id();
        // CONTRAINTE CRITIQUE : unique sur membre_id
        // Un membre appartient à UN SEUL groupement
        $table->foreignId('membre_id')
              ->unique()
              ->constrained('users')
              ->restrictOnDelete();
        $table->foreignId('groupement_id')
              ->constrained('groupements')
              ->restrictOnDelete();
        $table->date('date_adhesion');
        $table->enum('statut', ['actif', 'inactif'])->default('actif');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupement_membres');
    }
};
