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
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->string('sy')->nullable();
            $table->string('sydate');
            $table->string('completion');
            $table->string('current');
            $table->string('principal');
            $table->string('position');
            $table->string('signature')->nullable();
            $table->string('sds');
            $table->string('signature_sds')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_years');
    }
};
