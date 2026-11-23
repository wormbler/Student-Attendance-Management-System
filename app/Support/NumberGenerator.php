<?php

namespace App\Support;

use App\Models\Student;
use App\Models\Teacher;

class NumberGenerator
{
    // Call inside DB::transaction so the lock prevents duplicates.
    public static function student(): string
    {
        $prefix = now()->year.'-';

        $last = Student::where('student_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('student_number')
            ->value('student_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public static function teacher(): string
    {
        $prefix = 'T-';

        $last = Teacher::where('employee_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('employee_number')
            ->value('employee_number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}