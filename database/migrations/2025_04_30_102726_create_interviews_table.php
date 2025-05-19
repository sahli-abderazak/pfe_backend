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
       // Dans la migration créée
Schema::create('interviews', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('candidat_id');
    $table->unsignedBigInteger('offre_id');
    $table->unsignedBigInteger('recruteur_id');
    $table->dateTime('date_heure');
    $table->string('candidat_nom');
    $table->string('candidat_prenom');
    $table->string('candidat_email');
    $table->string('poste');
    $table->boolean('email_sent')->default(false);
    $table->timestamps();
    
    $table->foreign('candidat_id')->references('id')->on('candidats')->onDelete('cascade');
    $table->foreign('offre_id')->references('id')->on('offres')->onDelete('cascade');
    $table->foreign('recruteur_id')->references('id')->on('users')->onDelete('cascade');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interviews');
    }
};
