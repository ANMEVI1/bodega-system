<?php

namespace Database\Seeders;

use App\Models\SiteSection;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (SiteSection::DEFAULTS as $section => $content) {
            SiteSection::query()->firstOrCreate(['section' => $section], $content + ['active' => true]);
        }
    }
}
