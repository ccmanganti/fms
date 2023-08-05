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
        Schema::create('philbrgies', function (Blueprint $table) {
            $table->id();
            $table->string('brgyCode');
            $table->string('brgyDesc');
            $table->string('regCode');
            $table->string('provCode');
            $table->string('citymunCode');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('philbrgies');
    }
};
