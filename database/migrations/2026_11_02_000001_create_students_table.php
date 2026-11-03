<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number',30)->unique();
            $table->string('first_name',100);
            $table->string('last_name',100);
            $table->string('email')->unique();
            $table->string('course',120);
            $table->unsignedSmallInteger('year_level');
            $table->timestamps();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE students ENABLE ROW LEVEL SECURITY');
            DB::statement('ALTER TABLE students ADD CONSTRAINT students_year_check CHECK (year_level BETWEEN 1 AND 6)');
        }
    }
    public function down(): void { Schema::dropIfExists('students'); }
};