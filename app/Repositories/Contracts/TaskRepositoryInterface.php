<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TaskRepositoryInterface
{
    /**
     * Get paginated tasks for a project with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getForProject(Project $project, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a task for a project.
     */
    public function findForProject(Project $project, int $id): ?Task;

    /**
     * Create a task for a project.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForProject(Project $project, array $data): Task;

    /**
     * Update a task.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Task $task, array $data): Task;

    /**
     * Soft delete a task.
     */
    public function delete(Task $task): bool;
}
