<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:student,instructor,admin'],
            'admin_code' => ['nullable', 'string'],
        ]);

        if ($request->role === 'admin') {
            // Secret code to register as Admin
            if ($request->admin_code !== 'superadmin123') {
                return back()->withInput()->withErrors(['admin_code' => 'Kode rahasia Admin tidak valid.']);
            }
        }

        $roleName = match($request->role) {
            'admin' => Role::ROLE_ADMIN,
            'instructor' => Role::ROLE_INSTRUCTOR,
            default => Role::ROLE_STUDENT,
        };

        $roleModel = Role::where('name', $roleName)->firstOrFail();

        $user = User::create([
            'role_id' => $roleModel->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        Auth::login($user);

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard')->with('success', 'Selamat datang! Akun Admin Anda telah berhasil dibuat.');
        } elseif ($user->isInstructor()) {
            return redirect()->route('instructor.dashboard')->with('success', 'Selamat datang! Akun Instruktur Anda telah berhasil dibuat.');
        }

        return redirect()->route('student.dashboard')->with('success', 'Selamat datang! Akun Anda telah berhasil dibuat.');
    }
}
