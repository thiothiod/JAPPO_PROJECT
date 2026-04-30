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
    Schema::create('groupements', function (Blueprint $table) {
        $table->id();
        $table->string('nom')->unique();
        $table->foreignId('responsable_id')
              ->constrained('users')
              ->restrictOnDelete();
        $table->integer('montant_min');
        $table->integer('montant_max');
        $table->enum('jour_cotisation', [
            'lundi','mardi','mercredi',
            'jeudi','vendredi','samedi','dimanche'
        ]);
        $table->date('date_debut_cycle');
        $table->date('date_fin_cycle');  // debut + 364 jours
        $table->decimal('taux_interet', 5, 2)->default(5.00);
        $table->integer('plafond_pret')->nullable();
        $table->integer('duree_max_remboursement')->nullable(); // en mois
        $table->enum('approbation_pret', [
            'responsable_seul', 'vote_groupe', 'admin_uniquement'
        ])->default('responsable_seul');
        $table->enum('statut', ['actif', 'inactif', 'archive'])
              ->default('actif');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groupements');
    }
};
