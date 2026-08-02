<?php

namespace App\Repositories\Contracts;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectRepositoryInterface
{
    /**
     * Get paginated projects for a specific user with optional filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getForUser(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find a project belonging to a user by ID.
     */
    public function findForUser(User $user, int $id): ?Project;

    /**
     * Create a project for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createForUser(User $user, array $data): Project;

    /**
     * Update an existing project.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project;

    /**
     * Delete a project.
     */
    public function delete(Project $project): bool;
}
