<?php

namespace App\Observers;

use App\Models\Project;

class ProjectObserver
{
    /**
     * Handle the Project "deleting" event (before soft deletion).
     */
    public function deleting(Project $project): void
    {
        $project->tasks()->delete();
    }

    /**
     * Handle the Project "restored" event.
     */
    public function restored(Project $project): void
    {
        $project->tasks()->restore();
    }
}
