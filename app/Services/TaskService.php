<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskService
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository
    ) {}

    /**
     * Get list of tasks for a project.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getProjectTasks(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->getForProject($project, $filters, $perPage);
    }

    /**
     * Create a task for a project.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTask(Project $project, array $data): Task
    {
        return $this->taskRepository->createForProject($project, $data);
    }

    /**
     * Update an existing task.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTask(Task $task, array $data): Task
    {
        return $this->taskRepository->update($task, $data);
    }

    /**
     * Soft delete a task.
     */
    public function deleteTask(Task $task): bool
    {
        return $this->taskRepository->delete($task);
    }
}
