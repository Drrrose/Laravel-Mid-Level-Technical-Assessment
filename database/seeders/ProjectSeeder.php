<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'demo@example.com')->firstOrFail();

        // 5 projects across ALL statuses (active, completed, archived) for Demo User
        Project::factory()->create(['user_id' => $demoUser->id, 'name' => 'E-Commerce Platform', 'status' => ProjectStatus::Active]);
        Project::factory()->create(['user_id' => $demoUser->id, 'name' => 'Mobile App Revamp', 'status' => ProjectStatus::Active]);
        Project::factory()->create(['user_id' => $demoUser->id, 'name' => 'API Gateway Refactor', 'status' => ProjectStatus::Completed]);
        Project::factory()->create(['user_id' => $demoUser->id, 'name' => 'Legacy Migration', 'status' => ProjectStatus::Completed]);
        Project::factory()->create(['user_id' => $demoUser->id, 'name' => 'Q3 Analytics Dashboard', 'status' => ProjectStatus::Archived]);

        // 2-3 projects for each of the other users
        $otherUsers = User::where('email', '!=', 'demo@example.com')->get();

        foreach ($otherUsers as $user) {
            Project::factory()->count(2)->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
