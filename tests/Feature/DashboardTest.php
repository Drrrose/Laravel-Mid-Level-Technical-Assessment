<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_user_gets_correct_dashboard_metrics(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Active project for main user
        $activeProject = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Active,
        ]);

        // Completed project for main user
        $completedProject = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Completed,
        ]);

        // Trashed project for main user (should be excluded)
        $trashedProject = Project::factory()->create([
            'user_id' => $user->id,
            'status' => ProjectStatus::Active,
        ]);
        $trashedProject->delete();

        // Project for another user (should be excluded)
        $otherProject = Project::factory()->create([
            'user_id' => $otherUser->id,
            'status' => ProjectStatus::Active,
        ]);
        Task::factory()->create(['project_id' => $otherProject->id, 'status' => TaskStatus::Done]);

        // Tasks for main user's active project:
        // 1. Completed task
        Task::factory()->create([
            'project_id' => $activeProject->id,
            'status' => TaskStatus::Done,
            'due_date' => now()->subDays(5),
        ]);

        // 2. Pending task (Todo), not overdue
        Task::factory()->create([
            'project_id' => $activeProject->id,
            'status' => TaskStatus::Todo,
            'due_date' => now()->addDays(5),
        ]);

        // 3. Pending task (InProgress), overdue
        Task::factory()->create([
            'project_id' => $activeProject->id,
            'status' => TaskStatus::InProgress,
            'due_date' => now()->subDays(2),
        ]);

        // 4. Task in completed project (Todo), overdue
        Task::factory()->create([
            'project_id' => $completedProject->id,
            'status' => TaskStatus::Todo,
            'due_date' => now()->subDays(3),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Dashboard stats retrieved successfully',
                'data' => [
                    'total_projects' => 2,
                    'active_projects' => 1,
                    'total_tasks' => 4,
                    'completed_tasks' => 1,
                    'pending_tasks' => 3,
                    'overdue_tasks' => 2,
                ],
            ]);
    }
}
