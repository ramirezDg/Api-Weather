<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class CurrentWeather extends Command
{
    protected $signature = 'current {location=Santander,ES} {--units=metric}';
    protected $description = 'Get the current weather data for the given location.';

    public function handle()
    {
        $location = $this->argument('location');
        $units = $this->option('units');
        $apiKey = config('weather.api_key');
        $baseUrl = config('weather.base_url_current');

        $client = new Client();

        try {
            $response = $client->get("{$baseUrl}weather", [
                'query' => [
                    'q' => $location,
                    'units' => $units,
                    'appid' => $apiKey,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            $this->info("{$data['name']} ({$data['sys']['country']})");
            $this->info(date('M d, Y'));
            $this->info("> Weather: {$data['weather'][0]['description']}");
            $this->info("> Temperature: {$data['main']['temp']} °" . ($units == 'metric' ? 'C' : 'F'));
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $message = json_decode($response->getBody(), true)['message'];

            $this->error("Error ({$statusCode}): {$message}");
        }
    }
}
