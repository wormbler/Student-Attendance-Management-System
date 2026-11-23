<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $data = $request->validate(['password' => 'required|string|min:8']);
        $user->update(['password' => $data['password']]); // hashed by the User cast

        return back();
    }
}