<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(
        protected ProjectRepositoryInterface $projectRepository
    ) {}

    /**
     * Get list of projects owned by the user.
     *
     * @param  array<string, mixed>  $filters
     */
    public function getUserProjects(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->projectRepository->getForUser($user, $filters, $perPage);
    }

    /**
     * Create a project for the user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createProject(User $user, array $data): Project
    {
        return $this->projectRepository->createForUser($user, $data);
    }

    /**
     * Update an existing project.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProject(Project $project, array $data): Project
    {
        return $this->projectRepository->update($project, $data);
    }

    /**
     * Delete a project.
     */
    public function deleteProject(Project $project): bool
    {
        return $this->projectRepository->delete($project);
    }
}
