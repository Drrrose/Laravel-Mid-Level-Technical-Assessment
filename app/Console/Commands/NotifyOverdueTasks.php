<?php

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Jobs\SendOverdueTaskNotification;
use App\Models\Task;
use Illuminate\Console\Command;

class NotifyOverdueTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:notify-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find overdue tasks and dispatch notification jobs for project owners';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Task::where('due_date', '<', now())
            ->where('status', '!=', TaskStatus::Done)
            ->whereNull('overdue_notified_at')
            ->each(function (Task $task) {
                SendOverdueTaskNotification::dispatch($task);
            });

        return self::SUCCESS;
    }
}
