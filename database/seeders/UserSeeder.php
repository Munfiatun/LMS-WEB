<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing']) || ! config('auth.demo_user_password')) {
            return;
        }

        $adminRole = Role::where('name', Role::ROLE_ADMIN)->firstOrFail();
        $instructorRole = Role::where('name', Role::ROLE_INSTRUCTOR)->firstOrFail();
        $studentRole = Role::where('name', Role::ROLE_STUDENT)->firstOrFail();

        // 1. Admin Demo Account
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'role_id' => $adminRole->id,
                'name' => 'System Administrator',
                'password' => Hash::make(config('auth.demo_user_password')),
                'bio' => 'Lead Administrator for LCMS platform.',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 2. Instructor Demo Account
        User::firstOrCreate(
            ['email' => 'instructor@example.com'],
            [
                'role_id' => $instructorRole->id,
                'name' => 'Primary S.Pd',
                'password' => Hash::make(config('auth.demo_user_password')),
                'bio' => 'Senior Instructor specializing in Computer Science and Web Development.',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        // 3. Student Demo Account
        User::firstOrCreate(
            ['email' => 'student@example.com'],
            [
                'role_id' => $studentRole->id,
                'name' => 'Ruby',
                'password' => Hash::make(config('auth.demo_user_password')),
                'bio' => 'Enthusiastic student exploring modern software engineering.',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
