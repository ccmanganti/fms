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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('school_year_id');
            $table->string('completion')->nullable();
            $table->string('name');
            $table->string('grade_level');
            $table->string('section');
            $table->string('track_course');
            $table->string('adviser_id')->nullable();
            $table->json('subjects')->nullable();
            $table->json('students')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
