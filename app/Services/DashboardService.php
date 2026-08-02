<?php

namespace App\Services;

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;

class DashboardService
{
    /**
     * Get aggregate statistics for the user's dashboard.
     *
     * @return array{
     *     total_projects: int,
     *     active_projects: int,
     *     total_tasks: int,
     *     completed_tasks: int,
     *     pending_tasks: int,
     *     overdue_tasks: int
     * }
     */
    public function getStatsForUser(User $user): array
    {
        $projectIdsQuery = $user->projects()->select('id');

        $totalProjects = $user->projects()->count();
        $activeProjects = $user->projects()->where('status', ProjectStatus::Active)->count();

        $tasksQuery = Task::whereIn('project_id', $projectIdsQuery);

        $totalTasks = (clone $tasksQuery)->count();
        $completedTasks = (clone $tasksQuery)->where('status', TaskStatus::Done)->count();
        $pendingTasks = (clone $tasksQuery)->whereIn('status', [TaskStatus::Todo, TaskStatus::InProgress])->count();
        $overdueTasks = (clone $tasksQuery)
            ->where('due_date', '<', now())
            ->where('status', '!=', TaskStatus::Done)
            ->count();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $pendingTasks,
            'overdue_tasks' => $overdueTasks,
        ];
    }
}
