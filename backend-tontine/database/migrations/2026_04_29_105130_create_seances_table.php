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
    Schema::create('seances', function (Blueprint $table) {
        $table->id();
        $table->foreignId('groupement_id')
              ->constrained('groupements')
              ->cascadeOnDelete();
        $table->integer('numero_seance'); // 1 à 52
        $table->date('date_seance');
        $table->enum('statut', [
            'a_venir', 'en_cours', 'terminee', 'annulee'
        ])->default('a_venir');
        $table->text('note')->nullable();
        $table->timestamps();

        // Un groupement ne peut pas avoir 2 séances avec le même numéro
        $table->unique(['groupement_id', 'numero_seance']);
        // Ni 2 séances à la même date
        $table->unique(['groupement_id', 'date_seance']);
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seances');
    }
};
