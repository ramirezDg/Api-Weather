<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\CurrentWeather::class,
        \App\Console\Commands\ForecastWeather::class,
        \App\Console\Commands\ForecastWeatherAsk::class,
    ];

    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('        phpunit/console.php');
    }
}
