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
        Schema::table('users', function (Blueprint $table) {
            $table->text('apropos')->nullable();
            $table->string('lien_site_web')->nullable();
            $table->string('fax')->nullable();
            $table->string('domaine_activite')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['apropos', 'lien_site_web', 'fax', 'domaine_activite']);
        });
    }
};
