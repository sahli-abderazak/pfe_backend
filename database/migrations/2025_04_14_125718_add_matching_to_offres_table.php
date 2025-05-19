<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->float('matching')->nullable(); // ou double selon ton besoin
        });
    }

    public function down()
    {
        Schema::table('offres', function (Blueprint $table) {
            $table->dropColumn('matching');
        });
    }
};