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
        Schema::table('matching_scores', function (Blueprint $table) {
            $table->text('evaluation')->nullable();
            $table->json('points_forts')->nullable();
            $table->json('ecarts')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matching_scores', function (Blueprint $table) {
            $table->dropColumn(['evaluation', 'points_forts', 'ecarts']);
        });
    }
};
