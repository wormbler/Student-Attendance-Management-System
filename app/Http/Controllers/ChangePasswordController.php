<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ChangePasswordController extends Controller
{
    public function show(Request $request)
    {
        return Inertia::render('school/ChangePassword', [
            'forced' => (bool) $request->user()->must_change_password,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        $user = $request->user();
        $user->update([
            'password' => $data['password'], // hashed by the User cast
            'must_change_password' => false,
        ]);

        return redirect($user->role === 'student' ? '/my-attendance' : '/school');
    }
}