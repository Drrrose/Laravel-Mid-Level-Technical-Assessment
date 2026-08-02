<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Jobs\SendOverdueTaskNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class OverdueNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_overdue_task_dispatches_notification_job(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $overdueTask = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::InProgress,
            'due_date' => now()->subDays(2),
            'overdue_notified_at' => null,
        ]);

        $this->artisan('tasks:notify-overdue')
            ->assertSuccessful();

        Bus::assertDispatched(SendOverdueTaskNotification::class, function (SendOverdueTaskNotification $job) use ($overdueTask) {
            return $job->task->id === $overdueTask->id;
        });
    }

    public function test_job_handle_creates_database_notification_and_sets_overdue_notified_at(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $overdueTask = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Todo,
            'due_date' => now()->subDays(3),
            'overdue_notified_at' => null,
        ]);

        $job = new SendOverdueTaskNotification($overdueTask);
        $job->handle();

        $this->assertNotNull($overdueTask->fresh()->overdue_notified_at);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
        ]);
    }

    public function test_running_command_twice_only_notifies_once(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        $overdueTask = Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Todo,
            'due_date' => now()->subDays(1),
            'overdue_notified_at' => null,
        ]);

        // First run dispatches job
        $this->artisan('tasks:notify-overdue')->assertSuccessful();
        Bus::assertDispatched(SendOverdueTaskNotification::class, 1);

        // Simulate job completion
        $overdueTask->update(['overdue_notified_at' => now()]);

        // Second run should not dispatch job again
        Bus::fake();
        $this->artisan('tasks:notify-overdue')->assertSuccessful();
        Bus::assertNotDispatched(SendOverdueTaskNotification::class);
    }

    public function test_future_due_date_or_done_tasks_are_not_notified(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $project = Project::factory()->create(['user_id' => $user->id]);

        // Future task
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Todo,
            'due_date' => now()->addDays(2),
            'overdue_notified_at' => null,
        ]);

        // Completed task (overdue date but status is done)
        Task::factory()->create([
            'project_id' => $project->id,
            'status' => TaskStatus::Done,
            'due_date' => now()->subDays(5),
            'overdue_notified_at' => null,
        ]);

        $this->artisan('tasks:notify-overdue')->assertSuccessful();

        Bus::assertNotDispatched(SendOverdueTaskNotification::class);
    }
}
