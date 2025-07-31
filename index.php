<?php
$page_title = 'Dashboard';
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/weather_api.php';

// Get user's default location or use a default
$location = 'London';
$unit = 'C';

if (isLoggedIn()) {
    $user_details = getUserDetails($_SESSION['user_id']);
    if ($user_details['success']) {
        if (!empty($user_details['user']['default_location'])) {
            $location = $user_details['user']['default_location'];
        }
        if (!empty($user_details['user']['temperature_unit'])) {
            $unit = $user_details['user']['temperature_unit'];
        }
    }
}

// Check if location is provided in URL
if (isset($_GET['location'])) {
    $location = $_GET['location'];
}

// Check if coordinates are provided
if (isset($_GET['lat']) && isset($_GET['lon'])) {
    $location = $_GET['lat'] . ',' . $_GET['lon'];
}

// Get weather data
$weather_data = getCurrentWeather($location);
$forecast_data = getForecast($location, 3);
$astronomy_data = getAstronomy($location);

// Check if there was an error with the API
$has_error = isset($weather_data['error']) && $weather_data['error'] === true;

// Set custom background based on weather condition if no error
if (!$has_error && isset($weather_data['current']['condition']['code'])) {
    $condition_code = $weather_data['current']['condition']['code'];
    $is_day = $weather_data['current']['is_day'] == 1;
    $weather_theme = getWeatherTheme($condition_code);
    
    // Set custom background color for the page
    if (is_array($weather_theme)) {
        if (isset($weather_theme['day']) && isset($weather_theme['night'])) {
            $theme = $is_day ? $weather_theme['day'] : $weather_theme['night'];
            $custom_bg_color = $theme['bg_color'];
            $custom_text_color = $theme['text_color'];
        } else {
            $custom_bg_color = $weather_theme['bg_color'];
            $custom_text_color = $weather_theme['text_color'];
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="dashboard">
    <?php if ($has_error): ?>
        <div class="alert alert-danger">
            <?php echo $weather_data['message']; ?>
        </div>
        <div class="search-container">
            <form class="search-box">
                <input type="text" name="location" placeholder="Enter city name, zip/postal code or lat,lon" required>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
            <button class="btn btn-secondary location-btn">
                <i class="fas fa-map-marker-alt"></i> Current Location
            </button>
        </div>
    <?php else: ?>
        <div class="dashboard-header">
            <div class="location-info">
                <h1><?php echo $weather_data['location']['name']; ?>, <?php echo $weather_data['location']['country']; ?></h1>
                <p><?php echo date('l, F j, Y', strtotime($weather_data['location']['localtime'])); ?></p>
                <p>Last updated: <?php echo $weather_data['current']['last_updated']; ?></p>
            </div>
            <div class="search-container">
                <form class="search-box">
                    <input type="text" name="location" placeholder="Enter city name, zip/postal code or lat,lon" required>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </form>
                <button class="btn btn-secondary location-btn">
                    <i class="fas fa-map-marker-alt"></i> Current Location
                </button>
            </div>
            <div class="temp-toggle">
                <button class="celsius <?php echo $unit === 'C' ? 'active' : ''; ?>">°C</button>
                <button class="fahrenheit <?php echo $unit === 'F' ? 'active' : ''; ?>">°F</button>
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="card">
                <div class="card-body">
                    <div class="weather-widget">
                        <div class="weather-icon">
                            <i class="<?php echo getWeatherIcon($weather_data['current']['condition']['code'], $weather_data['current']['is_day']); ?>"></i>
                        </div>
                        <div class="weather-temp" data-temp-c="<?php echo $weather_data['current']['temp_c']; ?>" data-temp-f="<?php echo $weather_data['current']['temp_f']; ?>">
                            <?php echo $unit === 'C' ? $weather_data['current']['temp_c'] . '°C' : $weather_data['current']['temp_f'] . '°F'; ?>
                        </div>
                        <div class="weather-condition">
                            <?php echo $weather_data['current']['condition']['text']; ?>
                        </div>
                        <div class="weather-feels-like">
                            Feels like 
                            <span data-temp-c="<?php echo $weather_data['current']['feelslike_c']; ?>" data-temp-f="<?php echo $weather_data['current']['feelslike_f']; ?>">
                                <?php echo $unit === 'C' ? $weather_data['current']['feelslike_c'] . '°C' : $weather_data['current']['feelslike_f'] . '°F'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Weather Details</h3>
                </div>
                <div class="card-body">
                    <div class="weather-details">
                        <div class="weather-detail">
                            <i class="fas fa-tint"></i>
                            <span>Humidity: <?php echo $weather_data['current']['humidity']; ?>%</span>
                        </div>
                        <div class="weather-detail">
                            <i class="fas fa-wind"></i>
                            <span>Wind: <?php echo $unit === 'C' ? $weather_data['current']['wind_kph'] . ' km/h' : $weather_data['current']['wind_mph'] . ' mph'; ?></span>
                        </div>
                        <div class="weather-detail">
                            <i class="fas fa-compass"></i>
                            <span>Wind Direction: <?php echo $weather_data['current']['wind_dir']; ?> (<?php echo $weather_data['current']['wind_degree']; ?>°)</span>
                        </div>
                        <div class="weather-detail">
                            <i class="fas fa-compress-arrows-alt"></i>
                            <span>Pressure: <?php echo $unit === 'C' ? $weather_data['current']['pressure_mb'] . ' mb' : $weather_data['current']['pressure_in'] . ' in'; ?></span>
                        </div>
                        <div class="weather-detail">
                            <i class="fas fa-eye"></i>
                            <span>Visibility: <?php echo $unit === 'C' ? $weather_data['current']['vis_km'] . ' km' : $weather_data['current']['vis_miles'] . ' miles'; ?></span>
                        </div>
                        <div class="weather-detail">
                            <i class="fas fa-cloud"></i>
                            <span>Cloud Cover: <?php echo $weather_data['current']['cloud']; ?>%</span>
                        </div>
                        <div class="weather-detail">
                            <i class="fas fa-sun"></i>
                            <span>UV Index: <?php echo $weather_data['current']['uv']; ?></span>
                        </div>
                        <div class="weather-detail">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Gust: <?php echo $unit === 'C' ? $weather_data['current']['gust_kph'] . ' km/h' : $weather_data['current']['gust_mph'] . ' mph'; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">3-Day Forecast</h3>
                    <a href="forecast.php?location=<?php echo urlencode($location); ?>" class="btn btn-secondary">Detailed Forecast</a>
                </div>
                <div class="card-body">
                    <?php if (isset($forecast_data['forecast']['forecastday'])): ?>
                        <?php foreach ($forecast_data['forecast']['forecastday'] as $day): ?>
                            <div class="forecast-day">
                                <div class="forecast-date">
                                    <?php echo date('D, M j', strtotime($day['date'])); ?>
                                </div>
                                <div class="forecast-icon">
                                    <i class="<?php echo getWeatherIcon($day['day']['condition']['code'], true); ?>"></i>
                                </div>
                                <div class="forecast-condition">
                                    <?php echo $day['day']['condition']['text']; ?>
                                </div>
                                <div class="forecast-temp">
                                    <span data-temp-c="<?php echo $day['day']['maxtemp_c']; ?>" data-temp-f="<?php echo $day['day']['maxtemp_f']; ?>">
                                        <?php echo $unit === 'C' ? $day['day']['maxtemp_c'] . '°C' : $day['day']['maxtemp_f'] . '°F'; ?>
                                    </span>
                                    /
                                    <span data-temp-c="<?php echo $day['day']['mintemp_c']; ?>" data-temp-f="<?php echo $day['day']['mintemp_f']; ?>">
                                        <?php echo $unit === 'C' ? $day['day']['mintemp_c'] . '°C' : $day['day']['mintemp_f'] . '°F'; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($weather_data['current']['air_quality'])): ?>
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Air Quality</h3>
                </div>
                <div class="card-body">
                    <?php 
                    $aqi = isset($weather_data['current']['air_quality']['us-epa-index']) ? 
                        $weather_data['current']['air_quality']['us-epa-index'] : 1;
                    
                    $aqi_text = '';
                    $aqi_description = '';
                    
                    switch ($aqi) {
                        case 1:
                            $aqi_text = 'Good';
                            $aqi_description = 'Air quality is satisfactory, and air pollution poses little or no risk.';
                            break;
                        case 2:
                            $aqi_text = 'Moderate';
                            $aqi_description = 'Air quality is acceptable. However, there may be a risk for some people, particularly those who are unusually sensitive to air pollution.';
                            break;
                        case 3:
                            $aqi_text = 'Unhealthy for Sensitive Groups';
                            $aqi_description = 'Members of sensitive groups may experience health effects. The general public is less likely to be affected.';
                            break;
                        case 4:
                            $aqi_text = 'Unhealthy';
                            $aqi_description = 'Some members of the general public may experience health effects; members of sensitive groups may experience more serious health effects.';
                            break;
                        case 5:
                            $aqi_text = 'Very Unhealthy';
                            $aqi_description = 'Health alert: The risk of health effects is increased for everyone.';
                            break;
                        case 6:
                            $aqi_text = 'Hazardous';
                            $aqi_description = 'Health warning of emergency conditions: everyone is more likely to be affected.';
                            break;
                    }
                    ?>
                    
                    <div class="aqi-gauge" data-aqi="<?php echo $aqi; ?>">
                        <div class="aqi-meter"></div>
                        <div class="aqi-mask"></div>
                        <div class="aqi-indicator"></div>
                    </div>
                    
                    <div class="aqi-value">AQI</div>
                    <div class="aqi-label"><?php echo $aqi_text; ?></div>
                    <p class="aqi-description"><?php echo $aqi_description; ?></p>
                    
                    <div class="air-quality-details">
                        <div class="grid grid-2">
                            <div class="air-quality-item">
                                <div class="air-quality-label">PM2.5</div>
                                <div class="air-quality-value">
                                    <?php echo isset($weather_data['current']['air_quality']['pm2_5']) ? 
                                        number_format($weather_data['current']['air_quality']['pm2_5'], 1) : 'N/A'; ?>
                                </div>
                            </div>
                            <div class="air-quality-item">
                                <div class="air-quality-label">PM10</div>
                                <div class="air-quality-value">
                                    <?php echo isset($weather_data['current']['air_quality']['pm10']) ? 
                                        number_format($weather_data['current']['air_quality']['pm10'], 1) : 'N/A'; ?>
                                </div>
                            </div>
                            <div class="air-quality-item">
                                <div class="air-quality-label">O₃</div>
                                <div class="air-quality-value">
                                    <?php echo isset($weather_data['current']['air_quality']['o3']) ? 
                                        number_format($weather_data['current']['air_quality']['o3'], 1) : 'N/A'; ?>
                                </div>
                            </div>
                            <div class="air-quality-item">
                                <div class="air-quality-label">NO₂</div>
                                <div class="air-quality-value">
                                    <?php echo isset($weather_data['current']['air_quality']['no2']) ? 
                                        number_format($weather_data['current']['air_quality']['no2'], 1) : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-2">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Astronomy</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($astronomy_data['astronomy']['astro'])): ?>
                        <div class="astronomy-details">
                            <div class="grid grid-2">
                                <div class="astronomy-item">
                                    <div class="astronomy-icon">
                                        <i class="fas fa-sun"></i>
                                    </div>
                                    <div class="astronomy-label">Sunrise</div>
                                    <div class="astronomy-value"><?php echo $astronomy_data['astronomy']['astro']['sunrise']; ?></div>
                                </div>
                                <div class="astronomy-item">
                                    <div class="astronomy-icon">
                                        <i class="fas fa-moon"></i>
                                    </div>
                                    <div class="astronomy-label">Sunset</div>
                                    <div class="astronomy-value"><?php echo $astronomy_data['astronomy']['astro']['sunset']; ?></div>
                                </div>
                                <div class="astronomy-item">
                                    <div class="astronomy-icon">
                                        <i class="fas fa-cloud-moon"></i>
                                    </div>
                                    <div class="astronomy-label">Moonrise</div>
                                    <div class="astronomy-value"><?php echo $astronomy_data['astronomy']['astro']['moonrise']; ?></div>
                                </div>
                                <div class="astronomy-item">
                                    <div class="astronomy-icon">
                                        <i class="fas fa-cloud-moon"></i>
                                    </div>
                                    <div class="astronomy-label">Moonset</div>
                                    <div class="astronomy-value"><?php echo $astronomy_data['astronomy']['astro']['moonset']; ?></div>
                                </div>
                                <div class="astronomy-item">
                                    <div class="astronomy-icon">
                                        <i class="fas fa-moon"></i>
                                    </div>
                                    <div class="astronomy-label">Moon Phase</div>
                                    <div class="astronomy-value"><?php echo $astronomy_data['astronomy']['astro']['moon_phase']; ?></div>
                                </div>
                                <div class="astronomy-item">
                                    <div class="astronomy-icon">
                                        <i class="fas fa-adjust"></i>
                                    </div>
                                    <div class="astronomy-label">Moon Illumination</div>
                                    <div class="astronomy-value"><?php echo $astronomy_data['astronomy']['astro']['moon_illumination']; ?>%</div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Latest Weather News</h3>
                    <a href="news.php" class="btn btn-secondary">View All News</a>
                </div>
                <div class="card-body">
                    <?php 
                    $news = getWeatherNews($weather_data['location']['name']);
                    $latest_news = array_slice($news, 0, 2);
                    ?>
                    
                    <?php foreach ($latest_news as $item): ?>
                        <div class="news-item">
                            <div class="news-title">
                                <a href="news.php?id=<?php echo md5($item['title']); ?>"><?php echo $item['title']; ?></a>
                            </div>
                            <div class="news-meta">
                                <span class="news-date"><?php echo $item['date']; ?></span>
                                <span class="news-source"><?php echo $item['source']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?> 