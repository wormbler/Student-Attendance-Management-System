<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\ClassSchedule;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    private const ATTENDED = "SUM(attendances.status IN ('Present','Late'))";

    /** Attendance rows joined to session, schedule and student; teachers see only their own classes. */
    private function base($user)
    {
        return Attendance::query()
            ->join('attendance_sessions as s', 's.id', '=', 'attendances.session_id')
            ->join('class_schedules as c', 'c.id', '=', 's.schedule_id')
            ->join('students as st', 'st.id', '=', 'attendances.student_id')
            ->when($user->role === 'teacher', fn ($q) => $q->where('c.teacher_id', $user->teacher?->id ?? 0));
    }

    // ---------- Dashboard ----------

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        if ($user->role === 'teacher') {
            $teacherId = $user->teacher?->id ?? 0;
            $sectionIds = ClassSchedule::where('teacher_id', $teacherId)->pluck('section_id')->unique();
            $counts = [
                'My schedules' => ClassSchedule::where('teacher_id', $teacherId)->count(),
                'My sections' => $sectionIds->count(),
                'My students' => Student::whereIn('section_id', $sectionIds)->count(),
            ];
        } else {
            $counts = [
                'Sections' => Section::count(),
                'Students' => Student::count(),
                'Teachers' => Teacher::count(),
                'Subjects' => Subject::count(),
                'Schedules' => ClassSchedule::count(),
                'Attendance records' => Attendance::count(),
            ];
        }

        $todayCounts = $this->base($user)
            ->whereDate('s.session_date', $today)
            ->selectRaw('attendances.status as status, COUNT(*) as n')
            ->groupBy('attendances.status')
            ->pluck('n', 'status');

        $sessionsToday = AttendanceSession::whereDate('session_date', $today)
            ->when($user->role === 'teacher', fn ($q) => $q->whereHas(
                'schedule', fn ($s) => $s->where('teacher_id', $user->teacher?->id ?? 0)
            ))->count();

        $trend = $this->base($user)
            ->whereDate('s.session_date', '>=', now()->subDays(6)->toDateString())
            ->selectRaw('s.session_date as d, COUNT(*) as total, '.self::ATTENDED.' as attended')
            ->groupBy('s.session_date')->orderBy('s.session_date')->get()
            ->map(fn ($r) => [
                'date' => (string) $r->d,
                'total' => (int) $r->total,
                'rate' => $r->total ? round($r->attended / $r->total * 100, 1) : 0,
            ]);

        $overall = $this->base($user)
            ->whereDate('s.session_date', '>=', now()->subDays(29)->toDateString())
            ->selectRaw('COUNT(*) as total, '.self::ATTENDED.' as attended')->first();

        $low = $this->base($user)
            ->groupBy('st.id', 'st.student_number', 'st.first_name', 'st.last_name')
            ->selectRaw('st.id, st.student_number, st.first_name, st.last_name, COUNT(*) as total, '.self::ATTENDED.' as attended')
            ->havingRaw('COUNT(*) >= 3')
            ->havingRaw(self::ATTENDED.' / COUNT(*) < 0.75')
            ->orderByRaw(self::ATTENDED.' / COUNT(*) asc')
            ->limit(10)->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'student_number' => $r->student_number,
                'name' => "{$r->last_name}, {$r->first_name}",
                'total' => (int) $r->total,
                'rate' => round($r->attended / $r->total * 100, 1),
            ]);

        return Inertia::render('school/Dashboard', [
            'counts' => $counts,
            'today' => $todayCounts,
            'sessionsToday' => $sessionsToday,
            'rate30' => $overall && $overall->total ? round($overall->attended / $overall->total * 100, 1) : null,
            'trend' => $trend,
            'low' => $low,
        ]);
    }

    // ---------- Reports ----------

    private function reportRows(Request $request)
    {
        return $this->base($request->user())
            ->when($request->filled('section_id'), fn ($q) => $q->where('c.section_id', $request->input('section_id')))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('c.subject_id', $request->input('subject_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('s.session_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('s.session_date', '<=', $request->input('to')))
            ->groupBy('st.id', 'st.student_number', 'st.first_name', 'st.last_name')
            ->selectRaw(
                "st.id, st.student_number, st.first_name, st.last_name,
                 SUM(attendances.status = 'Present') as present,
                 SUM(attendances.status = 'Absent') as absent,
                 SUM(attendances.status = 'Late') as late,
                 SUM(attendances.status = 'Excused') as excused,
                 COUNT(*) as total"
            )
            ->orderBy('st.last_name')->orderBy('st.first_name')
            ->get()
            ->map(function ($r) {
                $total = (int) $r->total;

                return [
                    'id' => $r->id,
                    'student_number' => $r->student_number,
                    'name' => "{$r->last_name}, {$r->first_name}",
                    'present' => (int) $r->present,
                    'absent' => (int) $r->absent,
                    'late' => (int) $r->late,
                    'excused' => (int) $r->excused,
                    'total' => $total,
                    'rate' => $total ? round(((int) $r->present + (int) $r->late) / $total * 100, 1) : 0,
                ];
            });
    }

    public function index(Request $request)
    {
        return Inertia::render('school/Reports', [
            'rows' => $this->reportRows($request),
            'sections' => Section::orderBy('section_name')->get(['id', 'section_name']),
            'subjects' => Subject::orderBy('subject_name')->get(['id', 'subject_name']),
            'filters' => [
                'section_id' => $request->filled('section_id') ? (int) $request->input('section_id') : null,
                'subject_id' => $request->filled('subject_id') ? (int) $request->input('subject_id') : null,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->reportRows($request);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Student no.', 'Name', 'Present', 'Absent', 'Late', 'Excused', 'Total', 'Rate %']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['student_number'], $r['name'], $r['present'], $r['absent'],
                    $r['late'], $r['excused'], $r['total'], $r['rate'],
                ]);
            }
            fclose($out);
        }, 'attendance-report-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv']);
    }
}