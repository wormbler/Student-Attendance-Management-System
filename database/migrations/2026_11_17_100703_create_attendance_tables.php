<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'teacher', 'student'])
                ->default('student')
                ->after('password');
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_name', 100);
            $table->string('year_level', 50);
            $table->string('school_year', 20);
            $table->timestamps();
        });

        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->foreignId('section_id')->nullable()
                ->constrained('sections')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
        });

        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code', 50)->unique();
            $table->string('subject_name', 150);
            $table->decimal('units', 3, 1)->default(3.0);
            $table->timestamps();
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')
                ->constrained('subjects')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('teacher_id')
                ->constrained('teachers')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('section_id')
                ->constrained('sections')->restrictOnDelete()->cascadeOnUpdate();
            $table->enum('day', [
                'Monday', 'Tuesday', 'Wednesday', 'Thursday',
                'Friday', 'Saturday', 'Sunday',
            ]);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room', 100)->nullable();
            $table->timestamps();

            $table->index('day');
        });

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')
                ->constrained('class_schedules')->restrictOnDelete()->cascadeOnUpdate();
            $table->date('session_date');
            $table->foreignId('created_by')
                ->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['schedule_id', 'session_date']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained('students')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('session_id')
                ->constrained('attendance_sessions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->time('time_in')->nullable();
            $table->time('time_out')->nullable();
            $table->enum('status', [
                'Present', 'Absent', 'Late', 'Excused', 'Incomplete',
            ])->default('Absent');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'session_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('teachers');
        Schema::dropIfExists('students');
        Schema::dropIfExists('sections');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};