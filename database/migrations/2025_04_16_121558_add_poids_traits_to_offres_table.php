<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->integer('poids_ouverture')->default(0);
            $table->integer('poids_conscience')->default(0);
            $table->integer('poids_extraversion')->default(0);
            $table->integer('poids_agreabilite')->default(0);
            $table->integer('poids_stabilite')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->dropColumn([
                'poids_ouverture',
                'poids_conscience',
                'poids_extraversion',
                'poids_agreabilite',
                'poids_stabilite'
            ]);
        });
    }
};
