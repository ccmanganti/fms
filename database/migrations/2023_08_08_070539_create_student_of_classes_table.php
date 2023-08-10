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
        Schema::create('student_of_classes', function (Blueprint $table) {
            $table->id();
            $table->string('sem_1_status')->nullable();
            $table->string('sem_2_status')->nullable();
            $table->string('school_year_id');
            $table->string('name');
            $table->string('lrn');
            $table->string('lname');
            $table->string('fname');
            $table->string('mname');
            $table->string('gender')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('age')->nullable();
            $table->string('religion')->nullable();
            $table->string('no_street_purok')->nullable();
            $table->string('barangay')->nullable();
            $table->string('municipality')->nullable();
            $table->string('province')->nullable();
            $table->string('father_name')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('guardian')->nullable();
            $table->string('relationship')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('modality')->nullable();
            $table->string('student_type')->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_of_classes');
    }
};
