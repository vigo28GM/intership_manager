<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Evaluation;
use App\Models\Group;
use App\Models\GroupInternship;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Database\Seeder;

class InternshipSeeder extends Seeder
{
    public function run(): void
    {
        // Create groups
        $pt2024 = Group::create(['name' => 'PT2024']);
        $pt2023 = Group::create(['name' => 'PT2023']);
        $pt2022 = Group::create(['name' => 'PT2022']);

        // Create 50 users split into 3 groups
        User::factory(17)->create(['groups_id' => $pt2024->id]);
        User::factory(17)->create(['groups_id' => $pt2023->id]);
        User::factory(16)->create(['groups_id' => $pt2022->id]);

        // Create 2 internships
        $dbInternship = Internship::create([
            'name' => 'Database technology',
            'goals' => 'Learn database design, SQL, and optimization techniques',
        ]);

        $webInternship = Internship::create([
            'name' => 'Web Development',
            'goals' => 'Build modern web applications using latest technologies',
        ]);

        // Connect PT2024 with Database technology internship (starts in 7 days)
        GroupInternship::create([
            'group_id' => $pt2024->id,
            'internship_id' => $dbInternship->id,
            'start_at' => now()->addDays(7),
            'end_at' => now()->addMonths(3),
        ]);

        // Connect PT2023 with Web Development internship (ACTIVE NOW)
        GroupInternship::create([
            'group_id' => $pt2023->id,
            'internship_id' => $webInternship->id,
            'start_at' => now()->subDays(7),
            'end_at' => now()->addMonths(3),
        ]);

        // Connect PT2022 with Web Development internship (ACTIVE NOW)
        GroupInternship::create([
            'group_id' => $pt2022->id,
            'internship_id' => $webInternship->id,
            'start_at' => now()->subDays(7),
            'end_at' => now()->addMonths(3),
        ]);
    }
}
