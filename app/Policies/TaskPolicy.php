<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Determine whether the user can view any tasks in the project.
     */
    public function viewAny(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can view the task.
     */
    public function view(User $user, Task $task, ?Project $project = null): bool
    {
        $targetProject = $project ?? $task->project;

        return $user->id === $targetProject?->user_id && $task->project_id === $targetProject?->id;
    }

    /**
     * Determine whether the user can create tasks for the project.
     */
    public function create(User $user, Project $project): bool
    {
        return $user->id === $project->user_id;
    }

    /**
     * Determine whether the user can update the task.
     */
    public function update(User $user, Task $task, ?Project $project = null): bool
    {
        $targetProject = $project ?? $task->project;

        return $user->id === $targetProject?->user_id && $task->project_id === $targetProject?->id;
    }

    /**
     * Determine whether the user can delete the task.
     */
    public function delete(User $user, Task $task, ?Project $project = null): bool
    {
        $targetProject = $project ?? $task->project;

        return $user->id === $targetProject?->user_id && $task->project_id === $targetProject?->id;
    }
}
