<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class InstructorActivationController extends Controller
{
    public function store(User $user): RedirectResponse
    {
        abort_unless($user->isStudent() || $user->isInstructor(), 403);

        $user->update([
            'role_id' => Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail()->id,
            'is_active' => true,
        ]);

        return back()->with('success', 'Akun instruktur berhasil diaktifkan oleh Admin.');
    }
}
