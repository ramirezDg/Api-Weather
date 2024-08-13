<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ForecastWeather extends Command
{
    protected $signature = 'forecast {location=Santander,ES} {--days=1} {--units=metric}';
    protected $description = 'Get the weather forecast for the given location.';

    public function handle()
    {
        $location = $this->argument('location');
        $days = $this->option('days');
        $units = $this->option('units');
        $apiKey = config('weather.api_key');
        $baseUrl = config('weather.base_url_history');
        $baseUrlGeo = config('weather.base_url_geo');

        $client = new Client();

        try {
            $geoResponse = $client->get($baseUrlGeo, [
                'query' => [
                    'q' => $location,
                    'limit' => 1,
                    'appid' => $apiKey,
                ]
            ]);

            $geoData = json_decode($geoResponse->getBody(), true);
            if (empty($geoData)) {
                $this->error("No coordinates found for location: {$location}");
                return;
            }

            $lat = $geoData[0]['lat'];
            $lon = $geoData[0]['lon'];

            $this->info("Location: {$location} ({$lat}, {$lon})");

            $response = $client->get("{$baseUrl}forecast", [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'units' => $units,
                    'cnt' => $days * 8,
                    'appid' => $apiKey,
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if (isset($data['list'])) {
                $city = $data['city']['name'];
                $country = $data['city']['country'];
                $this->info("{$city} ({$country})");

                $shownDates = [];
                foreach ($data['list'] as $forecast) {
                    $date = date('Y-m-d', strtotime($forecast['dt_txt']));
                    if (!in_array($date, $shownDates)) {
                        $shownDates[] = $date;
                        setlocale(LC_TIME, 'en_US.UTF-8');
                        $formattedDate = strftime('%e de %B de %Y', strtotime($forecast['dt_txt']));
                        $weatherDescription = $forecast['weather'][0]['description'];
                        $temperature = $forecast['main']['temp'];
                        $temperatureUnit = $units == 'metric' ? 'C' : 'F';

                        $this->info("{$formattedDate}");
                        $this->info("> Weather: {$weatherDescription}");
                        $this->info("> Temperature: {$temperature} °{$temperatureUnit}");
                        $this->info("");

                        if (count($shownDates) >= $days) {
                            break;
                        }
                    }
                }
            } else {
                $this->error("No forecast data found for location: {$location}");
            }
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $statusCode = $response ? $response->getStatusCode() : 'N/A';
            $message = $response ? json_decode($response->getBody(), true)['message'] : $e->getMessage();

            $this->error("Error ({$statusCode}): {$message}");
        }
    }
}
