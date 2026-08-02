<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('tasks:notify-overdue')
    ->hourly()
    ->withoutOverlapping();
