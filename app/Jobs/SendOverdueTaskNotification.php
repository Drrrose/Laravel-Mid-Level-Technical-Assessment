<?php

namespace App\Jobs;

use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendOverdueTaskNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Task $task
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->task->overdue_notified_at !== null) {
            return;
        }

        $user = $this->task->project?->user;

        if ($user) {
            $user->notify(new TaskOverdueNotification($this->task));
        }

        $this->task->update([
            'overdue_notified_at' => now(),
        ]);
    }
}
