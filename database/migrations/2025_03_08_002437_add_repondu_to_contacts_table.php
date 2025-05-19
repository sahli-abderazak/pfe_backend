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
        Schema::table('contacts', function (Blueprint $table) {
            // Ajouter la colonne repondu si elle n'existe pas déjà
            if (!Schema::hasColumn('contacts', 'repondu')) {
                $table->boolean('repondu')->default(false);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            // Supprimer la colonne si elle existe
            if (Schema::hasColumn('contacts', 'repondu')) {
                $table->dropColumn('repondu');
            }
        });
    }
};