<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    

    public function up(): void
{
    DB::statement("ALTER TABLE cotisations DROP CONSTRAINT cotisations_statut_check");
    DB::statement("ALTER TABLE cotisations ADD CONSTRAINT cotisations_statut_check 
        CHECK (statut IN ('enregistree','validee','annulee','rattrapee','manquante'))");
}

public function down(): void
{
    DB::statement("ALTER TABLE cotisations DROP CONSTRAINT cotisations_statut_check");
    DB::statement("ALTER TABLE cotisations ADD CONSTRAINT cotisations_statut_check 
        CHECK (statut IN ('enregistree','validee','annulee','rattrapee'))");
}
};
