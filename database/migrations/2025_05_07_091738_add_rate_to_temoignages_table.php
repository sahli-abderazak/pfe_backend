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
        Schema::table('temoignages', function (Blueprint $table) {
            $table->integer('rate')->nullable()->after('valider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('temoignages', function (Blueprint $table) {
            $table->dropColumn('rate');
        });
    }
};
