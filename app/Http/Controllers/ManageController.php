<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Support\NumberGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ManageController extends Controller
{
    // ---------- Subjects (admin) ----------

    public function subjects()
    {
        return Inertia::render('school/Subjects', [
            'subjects' => Subject::withCount('schedules')->orderBy('subject_name')->get(),
        ]);
    }

    public function storeSubject(Request $request)
    {
        $data = $request->validate([
            'subject_code' => 'required|string|max:50|unique:subjects,subject_code',
            'subject_name' => 'required|string|max:150',
            'units' => 'required|numeric|min:0|max:99',
        ]);

        Subject::create($data);

        return back();
    }

    public function destroySubject(Subject $subject)
    {
        if ($subject->schedules()->exists()) {
            return back()->withErrors(['delete' => 'This subject is used by a schedule. Delete the schedule first.']);
        }

        $subject->delete();

        return back();
    }

    // ---------- Sections and teachers (admin) ----------

    public function setup()
    {
        return Inertia::render('school/Setup', [
            'sections' => Section::orderBy('section_name')->get(),
            'teachers' => Teacher::with('user:id,email')->orderBy('last_name')->get(),
        ]);
    }

    public function storeSection(Request $request)
    {
        $data = $request->validate([
            'section_name' => 'required|string|max:100',
            'year_level' => 'required|string|max:50',
            'school_year' => 'required|string|max:20',
        ]);

        Section::create($data);

        return back();
    }

    public function storeTeacher(Request $request)
{
    $data = $request->validate([
        'first_name' => 'required|string|max:100',
        'last_name' => 'required|string|max:100',
        'email' => 'required|email|max:150|unique:users,email',
    ]);

    $password = Str::password(10, symbols: false);

    DB::transaction(function () use ($data, $password) {
        $user = User::create([
            'name' => "{$data['first_name']} {$data['last_name']}",
            'email' => $data['email'],
            'password' => $password,
            'role' => 'teacher',
            'must_change_password' => true,
        ]);

        Teacher::create([
            'employee_number' => NumberGenerator::teacher(),
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'user_id' => $user->id,
        ]);
    });

    return back()->with('generated', [
        'name' => "{$data['first_name']} {$data['last_name']}",
        'email' => $data['email'],
        'password' => $password,
    ]);
}

    // ---------- Schedules (admin creates, teachers edit their own) ----------

    public function schedules(Request $request)
    {
        $user = $request->user();

        $query = ClassSchedule::with(['subject', 'teacher', 'section'])
            ->orderBy('day')->orderBy('start_time');

        if ($user->role === 'teacher') {
            $query->where('teacher_id', $user->teacher?->id ?? 0);
        }

        $schedules = $query->get()->map(fn ($s) => [
            'id' => $s->id,
            'section' => $s->section->section_name,
            'subject' => $s->subject->subject_name,
            'teacher' => "{$s->teacher->last_name}, {$s->teacher->first_name}",
            'teacher_id' => $s->teacher_id,
            'day' => $s->day,
            'start_time' => substr($s->start_time, 0, 5),
            'end_time' => substr($s->end_time, 0, 5),
            'room' => $s->room,
        ]);

        $isAdmin = $user->role === 'admin';

        return Inertia::render('school/Schedules', [
            'schedules' => $schedules,
            'isAdmin' => $isAdmin,
            'subjects' => $isAdmin ? Subject::orderBy('subject_name')->get(['id', 'subject_name']) : [],
            'teachers' => $isAdmin
                ? Teacher::orderBy('last_name')->get()->map(fn ($t) => ['id' => $t->id, 'name' => "{$t->last_name}, {$t->first_name}"])
                : [],
            'sections' => $isAdmin ? Section::orderBy('section_name')->get(['id', 'section_name']) : [],
        ]);
    }

    public function storeSchedule(Request $request)
    {
        $data = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
        ]);

        if ($message = $this->conflict($data['teacher_id'], $data['section_id'], $data['day'], $data['start_time'], $data['end_time'])) {
            return back()->withErrors(['start_time' => $message]);
        }

        ClassSchedule::create($data);

        return back();
    }

    public function updateSchedule(Request $request, ClassSchedule $schedule)
    {
        $user = $request->user();

        // A teacher may only edit their own schedules
        if ($user->role === 'teacher' && $schedule->teacher_id !== $user->teacher?->id) {
            abort(403);
        }

        $data = $request->validate([
            'day' => 'required|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room' => 'nullable|string|max:100',
        ]);

        if ($message = $this->conflict($schedule->teacher_id, $schedule->section_id, $data['day'], $data['start_time'], $data['end_time'], $schedule->id)) {
            return back()->withErrors(['start_time' => $message]);
        }

        $schedule->update($data);

        return back();
    }

    public function destroySchedule(ClassSchedule $schedule)
    {
        if ($schedule->sessions()->exists()) {
            return back()->withErrors(['delete' => 'This schedule already has attendance sessions and cannot be deleted.']);
        }

        $schedule->delete();

        return back();
    }

    private function conflict($teacherId, $sectionId, $day, $start, $end, $ignoreId = null): ?string
    {
        $overlap = fn () => ClassSchedule::where('day', $day)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId));

        if ($overlap()->where('teacher_id', $teacherId)->exists()) {
            return 'This teacher already has a class that overlaps this time.';
        }

        if ($overlap()->where('section_id', $sectionId)->exists()) {
            return 'This section already has a class that overlaps this time.';
        }

        return null;
    }
}