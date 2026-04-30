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
    Schema::create('remboursements', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pret_id')
              ->constrained('prets')
              ->restrictOnDelete();
        $table->integer('montant');
        $table->enum('type', ['partiel', 'total']);
        $table->date('date_remboursement');
        $table->foreignId('enregistre_par')
              ->constrained('users')
              ->restrictOnDelete();
        $table->text('note')->nullable();
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remboursements');
    }
};
