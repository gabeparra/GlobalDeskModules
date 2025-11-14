<?php

namespace Modules\ApiBridge\Bootstrap;

use TorMorten\Eventy\Facades\Eventy;

class Schedule
{
    public static function register(): void
    {
        Eventy::addFilter('schedule', function ($schedule) {
            $schedule->command('apibridge:webhooks-process')
                ->everyFiveMinutes()
                ->withoutOverlapping(15);

            $schedule->command('apibridge:webhooks-clean-logs')
                ->dailyAt('02:00');

            return $schedule;
        });
    }
}


