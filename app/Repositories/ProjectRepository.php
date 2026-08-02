<?php

namespace App\Repositories;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * Get paginated projects for a specific user with optional status filter.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $user->projects()
            ->when(! empty($filters['status']), function ($query) use ($filters) {
                $query->where('status', $filters['status']);
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Find a project belonging to a user by ID.
     */
    public function findForUser(User $user, int $id): ?Project
    {
        return $user->projects()->find($id);
    }

    /**
     * Create a project for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Project
    {
        return $user->projects()->create($data);
    }

    /**
     * Update an existing project.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->fresh();
    }

    /**
     * Delete a project.
     */
    public function delete(Project $project): bool
    {
        return (bool) $project->delete();
    }
}
