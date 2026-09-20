<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::ROLE_ADMIN,
                'label' => 'Administrator',
            ],
            [
                'name' => Role::ROLE_INSTRUCTOR,
                'label' => 'Instructor / Guru',
            ],
            [
                'name' => Role::ROLE_STUDENT,
                'label' => 'Student / Siswa',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['name' => $role['name']],
                ['label' => $role['label']]
            );
        }
    }
}
