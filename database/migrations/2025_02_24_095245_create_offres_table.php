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
        Schema::create('offres', function (Blueprint $table) {
            $table->id();
            $table->string('departement');
            $table->string('poste');
            $table->text('description');
            $table->date('datePublication')->default(now()); // Date du jour par défaut
            $table->date('dateExpiration');
            $table->boolean('valider')->default(false); // Ajout du champ valider (false par défaut)

            $table->string('typePoste'); // Temps plein, CDD, CDI, etc.
            $table->string('typeTravail'); // Sur site, Hybride, Télétravail
            $table->string('heureTravail'); // Ex: "9h-17h"
            $table->string('niveauExperience'); // Ex: Débutant, Intermédiaire, Expert
            $table->string('niveauEtude'); // Ex: Bac+3, Bac+5
            $table->string('pays');
            $table->string('ville');
            $table->string('societe'); // Nom de la société
            $table->string('domaine'); // Ex: Informatique, Marketing
            $table->text('responsabilite'); // Liste des responsabilités
            $table->text('experience'); // Expérience requise
            $table->timestamps();
        });
    }  
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offres');
    }
};
