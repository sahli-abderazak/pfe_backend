<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOffreScoreTable extends Migration
{
    public function up()
    {
        Schema::create('offre_score', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offre_id');
            $table->unsignedBigInteger('candidat_id');
            $table->integer('score');
            $table->timestamps();

            $table->foreign('offre_id')->references('id')->on('offres')->onDelete('cascade');
            $table->foreign('candidat_id')->references('id')->on('candidats')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('offre_score');
    }
}