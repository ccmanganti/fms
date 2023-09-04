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
        Schema::create('subject_loads', function (Blueprint $table) {
            $table->id();
            $table->string('school_year_id');
            $table->string('teacher_id');
            $table->string('class_id');
            $table->string('subject_id');
            $table->string('semester')->nullable();
            $table->string('written')->nullable();
            $table->string('performance')->nullable();
            $table->string('quarterly')->nullable();
            $table->string('total_percentage')->nullable();
            $table->string('total_written_work_1_1')->nullable();
            $table->string('total_written_work_2_1')->nullable();
            $table->string('total_written_work_3_1')->nullable();
            $table->string('total_written_work_4_1')->nullable();
            $table->string('total_written_work_5_1')->nullable();
            $table->string('total_written_work_6_1')->nullable();
            $table->string('total_written_work_7_1')->nullable();
            $table->string('total_written_work_8_1')->nullable();
            $table->string('total_written_work_9_1')->nullable();
            $table->string('total_written_work_10_1')->nullable();
            $table->string('total_performance_task_1_1')->nullable();
            $table->string('total_performance_task_2_1')->nullable();
            $table->string('total_performance_task_3_1')->nullable();
            $table->string('total_performance_task_4_1')->nullable();
            $table->string('total_performance_task_5_1')->nullable();
            $table->string('total_performance_task_6_1')->nullable();
            $table->string('total_performance_task_7_1')->nullable();
            $table->string('total_performance_task_8_1')->nullable();
            $table->string('total_performance_task_9_1')->nullable();
            $table->string('total_performance_task_10_1')->nullable();
            $table->string('total_quarterly_exam_1')->nullable();
            $table->string('total_written_work_1_2')->nullable();
            $table->string('total_written_work_2_2')->nullable();
            $table->string('total_written_work_3_2')->nullable();
            $table->string('total_written_work_4_2')->nullable();
            $table->string('total_written_work_5_2')->nullable();
            $table->string('total_written_work_6_2')->nullable();
            $table->string('total_written_work_7_2')->nullable();
            $table->string('total_written_work_8_2')->nullable();
            $table->string('total_written_work_9_2')->nullable();
            $table->string('total_written_work_10_2')->nullable();
            $table->string('total_performance_task_1_2')->nullable();
            $table->string('total_performance_task_2_2')->nullable();
            $table->string('total_performance_task_3_2')->nullable();
            $table->string('total_performance_task_4_2')->nullable();
            $table->string('total_performance_task_5_2')->nullable();
            $table->string('total_performance_task_6_2')->nullable();
            $table->string('total_performance_task_7_2')->nullable();
            $table->string('total_performance_task_8_2')->nullable();
            $table->string('total_performance_task_9_2')->nullable();
            $table->string('total_performance_task_10_2')->nullable();
            $table->string('total_quarterly_exam_2')->nullable();
            $table->json('student_grades')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_loads');
    }
};
