<?php

use App\Models\Attendance;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

function secUser(string $role, array $extra = []): User
{
    return User::factory()->create(array_merge([
        'role' => $role,
        'is_active' => true,
        'must_change_password' => false,
    ], $extra));
}

function secTeacher(): array
{
    $user = secUser('teacher');
    $teacher = Teacher::create([
        'employee_number' => Str::random(8),
        'first_name' => 'Test',
        'last_name' => 'Teacher',
        'user_id' => $user->id,
    ]);

    return [$user, $teacher];
}

function secSchedule(Teacher $teacher, string $day): ClassSchedule
{
    $section = Section::create([
        'section_name' => 'Sec '.Str::random(4),
        'year_level' => 'Grade 7',
        'school_year' => '2026-2027',
    ]);
    $subject = Subject::create([
        'subject_code' => Str::random(6),
        'subject_name' => 'Subject '.Str::random(4),
        'units' => 3,
    ]);

    return ClassSchedule::create([
        'subject_id' => $subject->id,
        'teacher_id' => $teacher->id,
        'section_id' => $section->id,
        'day' => $day,
        'start_time' => '08:00:00',
        'end_time' => '09:00:00',
    ]);
}

function secStudent(ClassSchedule $schedule): Student
{
    return Student::create([
        'student_number' => Str::random(8),
        'first_name' => 'Stu',
        'last_name' => 'Dent',
        'section_id' => $schedule->section_id,
    ]);
}

// ---------- Access control ----------

it('redirects guests to login', function () {
    $this->get('/school')->assertRedirect('/login');
});

it('blocks students from staff pages', function (string $url) {
    $this->actingAs(secUser('student'))->get($url)->assertForbidden();
})->with([
    '/school', '/school/students', '/school/reports', '/school/records',
    '/school/attendance', '/school/schedules', '/school/users',
    '/school/audit', '/school/subjects', '/school/setup',
]);

it('blocks teachers from admin pages', function (string $url) {
    $this->actingAs(secUser('teacher'))->get($url)->assertForbidden();
})->with(['/school/users', '/school/subjects', '/school/setup', '/school/audit']);

it('blocks admins from the student page', function () {
    $this->actingAs(secUser('admin'))->get('/my-attendance')->assertForbidden();
});

it('lets admins open admin pages', function (string $url) {
    $this->actingAs(secUser('admin'))->get($url)->assertOk();
})->with(['/school', '/school/users', '/school/audit', '/school/reports', '/school/subjects']);

it('has no public registration', function () {
    $this->get('/register')->assertNotFound();
});

it('logs out deactivated users', function () {
    $this->actingAs(secUser('admin', ['is_active' => false]))
        ->get('/school')->assertRedirect('/login');
});

it('forces a password change when flagged', function () {
    $this->actingAs(secUser('admin', ['must_change_password' => true]))
        ->get('/school')->assertRedirect('/change-password');
});

// ---------- Teacher ownership ----------

it('stops a teacher from editing another teacher\'s schedule', function () {
    [$mine] = secTeacher();
    [, $other] = secTeacher();
    $schedule = secSchedule($other, 'Monday');

    $this->actingAs($mine)->put("/school/schedules/{$schedule->id}", [
        'day' => 'Tuesday', 'start_time' => '09:00', 'end_time' => '10:00',
    ])->assertForbidden();
});

it('stops a teacher from viewing another teacher\'s class', function () {
    [$mine] = secTeacher();
    [, $other] = secTeacher();
    $schedule = secSchedule($other, now()->englishDayOfWeek);

    $this->actingAs($mine)
        ->get('/school/attendance?schedule_id='.$schedule->id)
        ->assertForbidden();
});

it('stops a teacher from saving attendance for another teacher\'s class', function () {
    [$mine] = secTeacher();
    [, $other] = secTeacher();
    $schedule = secSchedule($other, now()->englishDayOfWeek);
    $student = secStudent($schedule);

    $this->actingAs($mine)->post('/school/attendance', [
        'schedule_id' => $schedule->id,
        'date' => now()->toDateString(),
        'records' => [['student_id' => $student->id, 'status' => 'Present']],
    ])->assertForbidden();

    expect(Attendance::count())->toBe(0);
});

// ---------- Attendance rules ----------

it('rejects attendance on the wrong weekday', function () {
    [$user, $teacher] = secTeacher();
    $schedule = secSchedule($teacher, now()->englishDayOfWeek);
    $student = secStudent($schedule);

    $this->actingAs($user)->post('/school/attendance', [
        'schedule_id' => $schedule->id,
        'date' => now()->subDay()->toDateString(),
        'records' => [['student_id' => $student->id, 'status' => 'Present']],
    ])->assertSessionHasErrors('attendance');

    expect(Attendance::count())->toBe(0);
});

it('rejects attendance for future dates', function () {
    [$user, $teacher] = secTeacher();
    $schedule = secSchedule($teacher, now()->englishDayOfWeek);
    $student = secStudent($schedule);

    $this->actingAs($user)->post('/school/attendance', [
        'schedule_id' => $schedule->id,
        'date' => now()->addWeek()->toDateString(),
        'records' => [['student_id' => $student->id, 'status' => 'Present']],
    ])->assertSessionHasErrors('attendance');

    expect(Attendance::count())->toBe(0);
});

it('requires a reason to change saved attendance', function () {
    [$user, $teacher] = secTeacher();
    $schedule = secSchedule($teacher, now()->englishDayOfWeek);
    $student = secStudent($schedule);

    $payload = fn (string $status, ?string $reason = null) => [
        'schedule_id' => $schedule->id,
        'date' => now()->toDateString(),
        'reason' => $reason,
        'records' => [['student_id' => $student->id, 'status' => $status]],
    ];

    $this->actingAs($user)->post('/school/attendance', $payload('Present'))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)->post('/school/attendance', $payload('Absent'))
        ->assertSessionHasErrors('reason');

    expect(Attendance::first()->status)->toBe('Present');

    $this->actingAs($user)->post('/school/attendance', $payload('Absent', 'Marked wrong student'))
        ->assertSessionHasNoErrors();

    expect(Attendance::first()->status)->toBe('Absent');
});