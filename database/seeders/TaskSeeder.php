<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $demoUser = User::where('email', 'demo@example.com')->firstOrFail();
        $demoProjects = $demoUser->projects;

        // Seed tasks for Demo User's projects (3-8 tasks per project, including explicit overdue and completed tasks)
        foreach ($demoProjects as $project) {
            // Overdue task 1 (InProgress)
            Task::factory()->create([
                'project_id' => $project->id,
                'title' => "{$project->name} - Critical Bugfix (Overdue)",
                'status' => TaskStatus::InProgress,
                'priority' => TaskPriority::High,
                'due_date' => now()->subDays(3),
            ]);

            // Overdue task 2 (Todo)
            Task::factory()->create([
                'project_id' => $project->id,
                'title' => "{$project->name} - Code Review (Overdue)",
                'status' => TaskStatus::Todo,
                'priority' => TaskPriority::Medium,
                'due_date' => now()->subDays(2),
            ]);

            // Completed task (Done)
            Task::factory()->create([
                'project_id' => $project->id,
                'title' => "{$project->name} - Initial Setup",
                'status' => TaskStatus::Done,
                'priority' => TaskPriority::Low,
                'due_date' => now()->subDays(10),
            ]);

            // Additional random tasks (3 per project to make 5-6 total)
            Task::factory()->count(3)->create([
                'project_id' => $project->id,
            ]);
        }

        // Seed tasks for other users' projects (3-5 tasks each)
        $otherProjects = Project::where('user_id', '!=', $demoUser->id)->get();

        foreach ($otherProjects as $project) {
            Task::factory()->count(4)->create([
                'project_id' => $project->id,
            ]);
        }
    }
}
