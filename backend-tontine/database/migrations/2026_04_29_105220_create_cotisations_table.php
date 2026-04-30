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
    Schema::create('cotisations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('membre_id')
              ->constrained('users')
              ->restrictOnDelete();
        $table->foreignId('groupement_id')
              ->constrained('groupements')
              ->restrictOnDelete();
        $table->foreignId('seance_id')
              ->constrained('seances')
              ->restrictOnDelete();
        $table->integer('montant'); // jamais nul ni négatif
        $table->enum('statut', [
            'enregistree', 'validee', 'annulee', 'rattrapee'
        ])->default('enregistree');
        $table->foreignId('enregistre_par')
              ->constrained('users')
              ->restrictOnDelete();
        $table->text('note')->nullable();
        $table->timestamps();

        // Un membre ne cotise qu'une seule fois par séance
        $table->unique(['membre_id', 'seance_id']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotisations');
    }
};
