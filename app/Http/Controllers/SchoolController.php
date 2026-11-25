<?php

namespace App\Http\Controllers;
use App\Models\AttendanceLog;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Support\NumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SchoolController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('school/Dashboard', [
            'counts' => [
                'Sections' => Section::count(),
                'Students' => Student::count(),
                'Teachers' => Teacher::count(),
                'Subjects' => Subject::count(),
                'Schedules' => ClassSchedule::count(),
                'Attendance records' => Attendance::count(),
            ],
        ]);
    }

    public function students(Request $request)
{
    $user = $request->user();
    $isTeacher = $user->role === 'teacher';

    $sectionIds = $isTeacher
        ? ClassSchedule::where('teacher_id', $user->teacher?->id ?? 0)->pluck('section_id')->unique()
        : collect();

    return Inertia::render('school/Students', [
        'students' => Student::with('section:id,section_name')
            ->when($isTeacher, fn ($q) => $q->whereIn('section_id', $sectionIds))
            ->orderBy('last_name')->orderBy('first_name')->get(),
        'sections' => Section::orderBy('section_name')
            ->when($isTeacher, fn ($q) => $q->whereIn('id', $sectionIds))
            ->get(['id', 'section_name']),
    ]);
}

    public function storeStudent(Request $request)
{
    $data = $request->validate([
        'first_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'gender' => 'nullable|in:Male,Female,Other',
        'section_id' => 'required|exists:sections,id',
        'email' => 'nullable|email|max:150|unique:users,email',
    ]);

    $password = ! empty($data['email']) ? Str::password(10, symbols: false) : null;

    DB::transaction(function () use ($data, $password) {
        $userId = null;

        if ($password) {
            $userId = User::create([
                'name' => "{$data['first_name']} {$data['last_name']}",
                'email' => $data['email'],
                'password' => $password,
                'role' => 'student',
                'must_change_password' => true,
            ])->id;
        }

        Student::create([
            'student_number' => NumberGenerator::student(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'gender' => $data['gender'] ?? null,
            'section_id' => $data['section_id'],
            'user_id' => $userId,
        ]);
    });

    return $password
        ? back()->with('generated', [
            'name' => "{$data['first_name']} {$data['last_name']}",
            'email' => $data['email'],
            'password' => $password,
        ])
        : back();
}

public function createStudentLogin(Request $request, Student $student)
{
    if ($student->user_id) {
        return back()->withErrors(['login' => 'This student already has a login.']);
    }

    $v = Validator::make($request->all(), [
        'email' => 'required|email|max:150|unique:users,email',
    ]);

    if ($v->fails()) {
        return back()->withErrors(['login' => $v->errors()->first()]);
    }

    $password = Str::password(10, symbols: false);

    DB::transaction(function () use ($request, $student, $password) {
        $user = User::create([
            'name' => "{$student->first_name} {$student->last_name}",
            'email' => $request->email,
            'password' => $password,
            'role' => 'student',
            'must_change_password' => true,
        ]);

        $student->update(['user_id' => $user->id]);
    });

    return back()->with('generated', [
        'name' => "{$student->first_name} {$student->last_name}",
        'email' => $request->email,
        'password' => $password,
    ]);
}

public function myAttendance(Request $request)
{
    $student = $request->user()->student;

    $records = collect();
    $classes = collect();

    if ($student) {
        $records = Attendance::with('session.schedule.subject')
            ->where('student_id', $student->id)->get()
            ->sortByDesc(fn ($a) => $a->session->session_date)->values()
            ->map(fn ($a) => [
                'id' => $a->id,
                'date' => $a->session->session_date->toDateString(),
                'subject' => $a->session->schedule->subject->subject_name,
                'status' => $a->status,
                'schedule_id' => $a->session->schedule_id,
            ]);

        $byScheduleStatus = $records->groupBy('schedule_id');

        $classes = ClassSchedule::with(['subject', 'teacher'])
            ->where('section_id', $student->section_id)
            ->orderBy('day')->orderBy('start_time')->get()
            ->map(function ($s) use ($byScheduleStatus) {
                $counts = ($byScheduleStatus[$s->id] ?? collect())->countBy('status');

                return [
                    'id' => $s->id,
                    'subject' => $s->subject->subject_name,
                    'code' => $s->subject->subject_code,
                    'teacher' => "{$s->teacher->first_name} {$s->teacher->last_name}",
                    'day' => $s->day,
                    'time' => substr($s->start_time, 0, 5).' - '.substr($s->end_time, 0, 5),
                    'room' => $s->room,
                    'present' => $counts['Present'] ?? 0,
                    'absent' => $counts['Absent'] ?? 0,
                    'late' => $counts['Late'] ?? 0,
                    'excused' => $counts['Excused'] ?? 0,
                ];
            });
    }

    return Inertia::render('school/MyAttendance', [
        'linked' => (bool) $student,
        'student' => $student ? "{$student->last_name}, {$student->first_name}" : null,
        'section' => $student?->section?->section_name,
        'summary' => $records->countBy('status'),
        'classes' => $classes,
        'records' => $records,
    ]);
}

    public function destroyStudent(Student $student)
    {
        if ($student->attendances()->exists()) {
            return back()->withErrors([
                'delete' => 'This student has attendance records and cannot be deleted.',
            ]);
        }

        $student->delete();

        return back();
    }

    public function attendance(Request $request)
    {
        $user = $request->user();
$schedules = ClassSchedule::with(['subject', 'section'])
    ->when($user->role === 'teacher', fn ($q) => $q->where('teacher_id', $user->teacher?->id ?? 0))
    ->get()->map(fn ($s) => [
    'id' => $s->id,
    'day' => $s->day,
    'label' => "{$s->section->section_name} | {$s->subject->subject_name} | {$s->day} " . substr($s->start_time, 0, 5),
]);

        $date = $request->query('date', now()->toDateString());
        $scheduleId = $request->query('schedule_id');
        $students = [];

        if ($scheduleId) {
            $schedule = ClassSchedule::findOrFail($scheduleId);

            if ($user->role === 'teacher' && $schedule->teacher_id !== $user->teacher?->id) {
    abort(403);
}

            $session = AttendanceSession::where('schedule_id', $schedule->id)
                ->whereDate('session_date', $date)->first();
            $existing = $session
                ? $session->attendances()->pluck('status', 'student_id')
                : collect();

            $students = Student::where('section_id', $schedule->section_id)
                ->orderBy('last_name')->orderBy('first_name')->get()
                ->map(fn ($st) => [
                    'id' => $st->id,
                    'student_number' => $st->student_number,
                    'name' => "{$st->last_name}, {$st->first_name}",
                    'status' => $existing[$st->id] ?? null,
                ]);
        }

        return Inertia::render('school/Attendance', [
            'schedules' => $schedules,
            'students' => $students,
            'filters' => ['schedule_id' => $scheduleId ? (int) $scheduleId : null, 'date' => $date],
        ]);
    }

    public function saveAttendance(Request $request)
{
    $data = $request->validate([
        'schedule_id' => 'required|exists:class_schedules,id',
        'date' => 'required|date',
        'reason' => 'nullable|string|max:255',
        'records' => 'required|array|min:1',
        'records.*.student_id' => 'required|exists:students,id',
        'records.*.status' => 'required|in:Present,Absent,Late,Excused,Incomplete',
    ]);

    $user = $request->user();
    $schedule = ClassSchedule::findOrFail($data['schedule_id']);

    // The date must fall on the schedule's day
    if (Carbon::parse($data['date'])->englishDayOfWeek !== $schedule->day) {
    return back()->withErrors([
        'attendance' => "This class is scheduled on {$schedule->day}. {$data['date']} is a ".Carbon::parse($data['date'])->englishDayOfWeek.'.',
    ]);
    }

    // No attendance for future dates
    if (Carbon::parse($data['date'])->isFuture()) {
    return back()->withErrors(['attendance' => 'You cannot take attendance for a future date.']);
    }

    // A teacher may only record attendance for their own classes
    if ($user->role === 'teacher' && $schedule->teacher_id !== $user->teacher?->id) {
        abort(403);
    }

    // Every student must belong to the schedule's section
    $validIds = Student::where('section_id', $schedule->section_id)->pluck('id');
    $submitted = collect($data['records'])->pluck('student_id');
    if ($submitted->diff($validIds)->isNotEmpty()) {
        return back()->withErrors(['attendance' => 'Some students do not belong to this section.']);
    }

    $session = AttendanceSession::where('schedule_id', $schedule->id)
        ->whereDate('session_date', $data['date'])->first();

    $existing = $session
        ? $session->attendances()->get()->keyBy('student_id')
        : collect();

    $changed = collect($data['records'])->filter(
        fn ($r) => isset($existing[$r['student_id']]) && $existing[$r['student_id']]->status !== $r['status']
    );

    if ($changed->isNotEmpty()) {
        $tooOld = Carbon::parse($data['date'])->lt(now()->subDays(7)->startOfDay());

        if ($user->role === 'teacher' && $tooOld) {
            return back()->withErrors([
                'attendance' => 'Teachers can only edit attendance within 7 days. Please ask the admin.',
            ]);
        }

        if (blank($data['reason'] ?? null)) {
            return back()->withErrors(['reason' => 'A reason is required when changing existing attendance.']);
        }
    }

    DB::transaction(function () use ($data, $user, $schedule, $session, $existing) {
        $session ??= AttendanceSession::create([
            'schedule_id' => $schedule->id,
            'session_date' => $data['date'],
            'created_by' => $user->id,
        ]);

        foreach ($data['records'] as $row) {
            $attendance = $existing[$row['student_id']] ?? null;

            if (! $attendance) {
                $attendance = Attendance::create([
                    'student_id' => $row['student_id'],
                    'session_id' => $session->id,
                    'status' => $row['status'],
                ]);

                AttendanceLog::create([
                    'attendance_id' => $attendance->id,
                    'changed_by' => $user->id,
                    'action' => 'created',
                    'new_status' => $row['status'],
                ]);
            } elseif ($attendance->status !== $row['status']) {
                $old = $attendance->status;

                $attendance->update([
                    'status' => $row['status'],
                    'edited_by' => $user->id,
                    'edited_at' => now(),
                ]);

                AttendanceLog::create([
                    'attendance_id' => $attendance->id,
                    'changed_by' => $user->id,
                    'action' => 'updated',
                    'old_status' => $old,
                    'new_status' => $row['status'],
                    'reason' => $data['reason'],
                ]);
            }
        }
    });

    return back();
}

    public function auditLog()
{
    $logs = AttendanceLog::with(['user:id,name', 'attendance.student', 'attendance.session.schedule.subject'])
        ->where('action', 'updated')
        ->latest()->limit(300)->get()
        ->map(fn ($l) => [
            'id' => $l->id,
            'when' => $l->created_at->format('Y-m-d H:i'),
            'who' => $l->user?->name,
            'action' => $l->action,
            'student' => "{$l->attendance->student->last_name}, {$l->attendance->student->first_name}",
            'subject' => $l->attendance->session->schedule->subject->subject_name,
            'date' => $l->attendance->session->session_date->toDateString(),
            'old_status' => $l->old_status,
            'new_status' => $l->new_status,
            'reason' => $l->reason,
        ]);

    return Inertia::render('school/Audit', ['logs' => $logs]);
}

    public function records(Request $request)
{
    $user = $request->user();

    $records = Attendance::with(['student', 'session.schedule.subject'])
        ->when($user->role === 'teacher', fn ($q) => $q->whereHas(
            'session.schedule',
            fn ($s) => $s->where('teacher_id', $user->teacher?->id ?? 0)
        ))
        ->latest('id')->limit(200)->get()->map(fn ($a) => [
            'id' => $a->id,
            'date' => $a->session->session_date->toDateString(),
            'student' => "{$a->student->last_name}, {$a->student->first_name}",
            'subject' => $a->session->schedule->subject->subject_name,
            'status' => $a->status,
        ]);

    return Inertia::render('school/Records', ['records' => $records]);
}
}