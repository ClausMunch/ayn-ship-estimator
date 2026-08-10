<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('scrape')->dailyAt('06:00')->withoutOverlapping();
Schedule::command('scrape')->dailyAt('18:00')->withoutOverlapping();
Schedule::command('notify')->dailyAt('06:30');
Schedule::command('notify')->dailyAt('18:30');
Schedule::command('mail:collect-bounces')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('subscribers:prune-unverified --days=7')->dailyAt('03:15')->withoutOverlapping();
