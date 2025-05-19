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
        Schema::table('scores_tests', function (Blueprint $table) {
            $table->integer('ouverture')->nullable();
            $table->integer('conscience')->nullable();
            $table->integer('extraversion')->nullable();
            $table->integer('agreabilite')->nullable();
            $table->integer('stabilite')->nullable();
        });
    }
    

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('scores_tests', function (Blueprint $table) {
            $table->dropColumn([
                'ouverture',
                'conscience',
                'extraversion',
                'agreabilite',
                'stabilite',
            ]);
        });
    }
    
};
