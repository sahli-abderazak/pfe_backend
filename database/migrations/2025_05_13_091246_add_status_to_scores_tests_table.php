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
            $table->enum('status', ['terminer', 'temps ecoule', 'tricher'])->nullable()->after('score_total');
        });
    }

    public function down()
    {
        Schema::table('scores_tests', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};