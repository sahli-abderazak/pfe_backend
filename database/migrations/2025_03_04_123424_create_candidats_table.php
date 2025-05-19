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
        Schema::create('candidats', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email');
            $table->string('pays');
            $table->string('ville');
            $table->string('codePostal');
            $table->string('tel');
            $table->string('niveauEtude'); // Ex: Bac+3, Bac+5
            $table->string('cv'); // Stocker le chemin du fichier CV

            // Clé étrangère vers la table offres
            $table->foreignId('offre_id')->constrained('offres')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidats');
    }
};
