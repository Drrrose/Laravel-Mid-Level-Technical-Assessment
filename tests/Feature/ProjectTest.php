<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_projects(): void
    {
        $this->getJson('/api/projects')->assertStatus(401);
        $this->postJson('/api/projects', ['name' => 'Test'])->assertStatus(401);
        $this->getJson('/api/projects/1')->assertStatus(401);
        $this->putJson('/api/projects/1', ['name' => 'Update'])->assertStatus(401);
        $this->deleteJson('/api/projects/1')->assertStatus(401);
    }

    public function test_user_can_create_project(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => 'Awesome App',
            'description' => 'Project description text',
            'status' => ProjectStatus::Active->value,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/projects', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Project created successfully',
                'data' => [
                    'name' => 'Awesome App',
                    'description' => 'Project description text',
                    'status' => 'active',
                    'user_id' => $user->id,
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'user_id' => $user->id,
            'name' => 'Awesome App',
            'status' => 'active',
        ]);
    }

    public function test_create_project_validation_fails_if_name_missing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/projects', ['description' => 'No name provided']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_user_can_list_only_their_own_projects(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Project::factory()->count(3)->create(['user_id' => $userA->id]);
        Project::factory()->count(2)->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/projects');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(3, $data);
        foreach ($data as $item) {
            $this->assertEquals($userA->id, $item['user_id']);
        }
    }

    public function test_user_can_filter_projects_by_status(): void
    {
        $user = User::factory()->create();

        Project::factory()->create(['user_id' => $user->id, 'status' => ProjectStatus::Active]);
        Project::factory()->create(['user_id' => $user->id, 'status' => ProjectStatus::Completed]);
        Project::factory()->create(['user_id' => $user->id, 'status' => ProjectStatus::Archived]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects?status=completed');

        $response->assertStatus(200);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('completed', $data[0]['status']);
    }

    public function test_user_can_view_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects/'.$project->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $project->id,
                    'name' => $project->name,
                ],
            ]);
    }

    public function test_user_cannot_view_another_users_project(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $projectB = Project::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA, 'sanctum')
            ->getJson('/api/projects/'.$projectB->id);

        $response->assertStatus(403);
    }

    public function test_user_can_update_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Name',
            'status' => ProjectStatus::Active,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'status' => ProjectStatus::Completed->value,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/projects/'.$project->id, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Project updated successfully',
                'data' => [
                    'id' => $project->id,
                    'name' => 'Updated Name',
                    'status' => 'completed',
                ],
            ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
            'status' => 'completed',
        ]);
    }

    public function test_user_cannot_update_another_users_project(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $projectB = Project::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA, 'sanctum')
            ->putJson('/api/projects/'.$projectB->id, ['name' => 'Hacked Name']);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_their_own_project(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/projects/'.$project->id);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Project deleted successfully',
            ]);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_user_cannot_delete_another_users_project(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $projectB = Project::factory()->create(['user_id' => $userB->id]);

        $response = $this->actingAs($userA, 'sanctum')
            ->deleteJson('/api/projects/'.$projectB->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('projects', ['id' => $projectB->id]);
    }
}
