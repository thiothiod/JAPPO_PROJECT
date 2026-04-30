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
    Schema::create('prets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('membre_id')
              ->constrained('users')
              ->restrictOnDelete();
        $table->foreignId('groupement_id')
              ->constrained('groupements')
              ->restrictOnDelete();
        $table->integer('montant');
        $table->decimal('taux_interet', 5, 2); // snapshot du taux au moment du prêt
        $table->integer('montant_interet');     // montant * taux
        $table->integer('montant_total_du');    // montant + montant_interet
        $table->enum('statut', [
            'en_attente', 'valide', 'refuse',
            'en_cours', 'rembourse'
        ])->default('en_attente');
        $table->date('date_demande');
        $table->date('date_validation')->nullable();
        $table->date('date_echeance')->nullable();
        $table->foreignId('valide_par')
              ->nullable()
              ->constrained('users')
              ->nullOnDelete();
        $table->text('motif_refus')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prets');
    }
};
