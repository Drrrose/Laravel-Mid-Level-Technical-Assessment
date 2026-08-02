<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_tasks(): void
    {
        $this->getJson('/api/projects/1/tasks')->assertStatus(401);
        $this->postJson('/api/projects/1/tasks', ['title' => 'Test'])->assertStatus(401);
        $this->getJson('/api/projects/1/tasks/1')->assertStatus(401);
        $this->putJson('/api/projects/1/tasks/1', ['title' => 'Update'])->assertStatus(401);
        $this->deleteJson('/api/projects/1/tasks/1')->assertStatus(401);
    }

    public function test_user_can_create_task_in_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $payload = [
            'title' => 'Implement Auth Module',
            'description' => 'Add Sanctum authentication and tests',
            'status' => TaskStatus::InProgress->value,
            'priority' => TaskPriority::High->value,
            'due_date' => '2026-12-31',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task created successfully',
                'data' => [
                    'title' => 'Implement Auth Module',
                    'description' => 'Add Sanctum authentication and tests',
                    'status' => 'in_progress',
                    'priority' => 'high',
                    'project_id' => $project->id,
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'project_id' => $project->id,
            'title' => 'Implement Auth Module',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }

    public function test_create_task_validation_fails_if_title_is_missing_or_enum_invalid(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/projects/{$project->id}/tasks", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'status']);
    }

    public function test_user_can_list_paginated_tasks_for_their_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->count(5)->create(['project_id' => $project->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks");

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(5, $data);
    }

    public function test_user_can_filter_tasks_by_status(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Todo]);
        Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::InProgress]);
        Task::factory()->create(['project_id' => $project->id, 'status' => TaskStatus::Done]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?status=in_progress");

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('in_progress', $data[0]['status']);
    }

    public function test_user_can_filter_tasks_by_priority(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->create(['project_id' => $project->id, 'priority' => TaskPriority::Low]);
        Task::factory()->create(['project_id' => $project->id, 'priority' => TaskPriority::High]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?priority=high");

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('high', $data[0]['priority']);
    }

    public function test_user_can_search_tasks_by_title(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        Task::factory()->create(['project_id' => $project->id, 'title' => 'Write Unit Tests']);
        Task::factory()->create(['project_id' => $project->id, 'title' => 'Deploy Application']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}/tasks?search=Unit");

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Write Unit Tests', $data[0]['title']);
    }

    public function test_user_can_update_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id, 'title' => 'Old Title', 'status' => TaskStatus::Todo]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/projects/{$project->id}/tasks/{$task->id}", [
                'title' => 'Updated Title',
                'status' => TaskStatus::Done->value,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $task->id,
                    'title' => 'Updated Title',
                    'status' => 'done',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
            'status' => 'done',
        ]);
    }

    public function test_user_can_soft_delete_task(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);
        $task = Task::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Task deleted successfully',
            ]);

        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    public function test_user_cannot_access_or_modify_another_users_project_tasks(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $projectB = Project::factory()->create(['user_id' => $userB->id]);
        $taskB = Task::factory()->create(['project_id' => $projectB->id]);

        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/projects/{$projectB->id}/tasks")
            ->assertStatus(403);

        $this->actingAs($userA, 'sanctum')
            ->postJson("/api/projects/{$projectB->id}/tasks", ['title' => 'Unauthorized'])
            ->assertStatus(403);

        $this->actingAs($userA, 'sanctum')
            ->getJson("/api/projects/{$projectB->id}/tasks/{$taskB->id}")
            ->assertStatus(403);

        $this->actingAs($userA, 'sanctum')
            ->putJson("/api/projects/{$projectB->id}/tasks/{$taskB->id}", ['title' => 'Hacked'])
            ->assertStatus(403);

        $this->actingAs($userA, 'sanctum')
            ->deleteJson("/api/projects/{$projectB->id}/tasks/{$taskB->id}")
            ->assertStatus(403);
    }

    public function test_deleting_project_cascades_soft_delete_to_tasks_and_restoring_restores_them(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $task1 = Task::factory()->create(['project_id' => $project->id]);
        $task2 = Task::factory()->create(['project_id' => $project->id]);

        // Soft delete project
        $project->delete();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertSoftDeleted('tasks', ['id' => $task1->id]);
        $this->assertSoftDeleted('tasks', ['id' => $task2->id]);

        // Restore project
        $project->restore();

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('tasks', ['id' => $task1->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('tasks', ['id' => $task2->id, 'deleted_at' => null]);
    }
}
