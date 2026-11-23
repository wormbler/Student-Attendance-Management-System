<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('school/Users', [
            'users' => User::with(['student:id,user_id,student_number', 'teacher:id,user_id,employee_number'])
                ->orderBy('role')->orderBy('name')->get()
                ->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'is_active' => $u->is_active,
                    'linked' => $u->student?->student_number ?? $u->teacher?->employee_number,
                ]),
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot change your own role.']);
        }

        $data = $request->validate(['role' => 'required|in:admin,teacher,student']);
        $user->update($data);

        return back();
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        return back();
    }

    public function resetPassword(Request $request, User $user)
{
    $password = Str::password(10, symbols: false);

    $user->update([
        'password' => $password,
        'must_change_password' => true,
    ]);

    return back()->with('generated', [
        'name' => $user->name,
        'email' => $user->email,
        'password' => $password,
    ]);
}
}