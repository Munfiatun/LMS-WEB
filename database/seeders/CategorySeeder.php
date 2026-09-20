<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Development',
                'slug' => 'web-development',
                'description' => 'Materi pemrograman web modern menggunakan PHP, Laravel, Tailwind CSS, dan JavaScript.',
                'icon' => 'code-bracket',
            ],
            [
                'name' => 'Artificial Intelligence',
                'slug' => 'artificial-intelligence',
                'description' => 'Konsep LLM, Retrieval-Augmented Generation, dan integrasi API AI pada aplikasi nyata.',
                'icon' => 'cpu-chip',
            ],
            [
                'name' => 'Cyber Security',
                'slug' => 'cyber-security',
                'description' => 'Keamanan aplikasi web, pencegahan kerentanan OWASP Top 10, dan secure coding.',
                'icon' => 'shield-check',
            ],
            [
                'name' => 'Cloud & DevOps',
                'slug' => 'cloud-devops',
                'description' => 'Penerapan CI/CD, kontainerisasi Docker, dan deployment aplikasi berskala tinggi.',
                'icon' => 'server-stack',
            ],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                $cat
            );
        }
    }
}
