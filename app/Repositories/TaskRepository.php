<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\Task;
use App\Repositories\Contracts\TaskRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TaskRepository implements TaskRepositoryInterface
{
    /**
     * Get paginated tasks for a project with status, priority, and search filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $project->tasks()
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->when(! empty($filters['priority']), function ($query) use ($filters) {
                $query->where('priority', $filters['priority']);
            })
            ->when(! empty($filters['search']), function ($query) use ($filters) {
                $query->where('title', 'like', '%'.$filters['search'].'%');
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a task for a project.
     */
    public function findForProject(Project $project, int $id): ?Task
    {
        return $project->tasks()->find($id);
    }

    /**
     * Create a task for a project.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForProject(Project $project, array $data): Task
    {
        return $project->tasks()->create($data);
    }

    /**
     * Update a task.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task
    {
        $task->update($data);

        return $task->fresh();
    }

    /**
     * Soft delete a task.
     */
    public function delete(Task $task): bool
    {
        return (bool) $task->delete();
    }
}
