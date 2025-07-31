<?php
$page_title = 'Weather Forecast';
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/weather_api.php';

// Get user's default location or use a default
$location = 'London';
$unit = 'C';
$days = 7; // Default to 7-day forecast

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

// Check if days are specified
if (isset($_GET['days']) && is_numeric($_GET['days']) && $_GET['days'] > 0 && $_GET['days'] <= 14) {
    $days = $_GET['days'];
}

// Get forecast data
$forecast_data = getForecast($location, $days);

// Check if there was an error with the API
$has_error = isset($forecast_data['error']) && $forecast_data['error'] === true;

// Include header
include 'includes/header.php';
?>

<div class="forecast-page">
    <div class="page-header">
        <h1>Weather Forecast</h1>
        <div class="search-container">
            <form class="search-box">
                <input type="text" name="location" placeholder="Enter city name, zip/postal code or lat,lon" value="<?php echo htmlspecialchars($location); ?>" required>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            </form>
            <button class="btn btn-secondary location-btn">
                <i class="fas fa-map-marker-alt"></i> Current Location
            </button>
        </div>
        <div class="forecast-controls">
            <div class="days-selector">
                <label>Forecast Days:</label>
                <select id="forecast-days" onchange="changeForecastDays(this.value)">
                    <option value="3" <?php echo $days == 3 ? 'selected' : ''; ?>>3 Days</option>
                    <option value="7" <?php echo $days == 7 ? 'selected' : ''; ?>>7 Days</option>
                    <option value="14" <?php echo $days == 14 ? 'selected' : ''; ?>>14 Days</option>
                </select>
            </div>
            <div class="temp-toggle">
                <button class="celsius <?php echo $unit === 'C' ? 'active' : ''; ?>">°C</button>
                <button class="fahrenheit <?php echo $unit === 'F' ? 'active' : ''; ?>">°F</button>
            </div>
        </div>
    </div>
    
    <?php if ($has_error): ?>
        <div class="alert alert-danger">
            <?php echo $forecast_data['message']; ?>
        </div>
    <?php else: ?>
        <div class="location-info">
            <h2><?php echo $forecast_data['location']['name']; ?>, <?php echo $forecast_data['location']['country']; ?></h2>
            <p>Local Time: <?php echo $forecast_data['location']['localtime']; ?></p>
        </div>
        
        <div class="forecast-tabs">
            <div class="tab-buttons">
                <button class="tab-btn active" data-tab="daily">Daily Forecast</button>
                <button class="tab-btn" data-tab="hourly">Hourly Forecast</button>
            </div>
            
            <div class="tab-content">
                <div class="tab-pane active" id="daily-forecast">
                    <?php if (isset($forecast_data['forecast']['forecastday'])): ?>
                        <div class="forecast-cards">
                            <?php foreach ($forecast_data['forecast']['forecastday'] as $day): ?>
                                <div class="forecast-card">
                                    <div class="forecast-card-header">
                                        <div class="forecast-date">
                                            <h3><?php echo date('D, M j', strtotime($day['date'])); ?></h3>
                                            <p><?php echo date('Y', strtotime($day['date'])); ?></p>
                                        </div>
                                    </div>
                                    <div class="forecast-card-body">
                                        <div class="forecast-icon">
                                            <i class="<?php echo getWeatherIcon($day['day']['condition']['code'], true); ?>"></i>
                                        </div>
                                        <div class="forecast-condition">
                                            <?php echo $day['day']['condition']['text']; ?>
                                        </div>
                                        <div class="forecast-temp">
                                            <div class="high-temp">
                                                <i class="fas fa-temperature-high"></i>
                                                <span data-temp-c="<?php echo $day['day']['maxtemp_c']; ?>" data-temp-f="<?php echo $day['day']['maxtemp_f']; ?>">
                                                    <?php echo $unit === 'C' ? $day['day']['maxtemp_c'] . '°C' : $day['day']['maxtemp_f'] . '°F'; ?>
                                                </span>
                                            </div>
                                            <div class="low-temp">
                                                <i class="fas fa-temperature-low"></i>
                                                <span data-temp-c="<?php echo $day['day']['mintemp_c']; ?>" data-temp-f="<?php echo $day['day']['mintemp_f']; ?>">
                                                    <?php echo $unit === 'C' ? $day['day']['mintemp_c'] . '°C' : $day['day']['mintemp_f'] . '°F'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="forecast-details">
                                            <div class="forecast-detail">
                                                <i class="fas fa-tint"></i>
                                                <span>Humidity: <?php echo $day['day']['avghumidity']; ?>%</span>
                                            </div>
                                            <div class="forecast-detail">
                                                <i class="fas fa-wind"></i>
                                                <span>Wind: <?php echo $unit === 'C' ? $day['day']['maxwind_kph'] . ' km/h' : $day['day']['maxwind_mph'] . ' mph'; ?></span>
                                            </div>
                                            <div class="forecast-detail">
                                                <i class="fas fa-umbrella"></i>
                                                <span>Precipitation: <?php echo $unit === 'C' ? $day['day']['totalprecip_mm'] . ' mm' : $day['day']['totalprecip_in'] . ' in'; ?></span>
                                            </div>
                                            <div class="forecast-detail">
                                                <i class="fas fa-sun"></i>
                                                <span>UV Index: <?php echo $day['day']['uv']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="forecast-card-footer">
                                        <button class="btn btn-primary show-hourly" data-date="<?php echo $day['date']; ?>">
                                            <i class="fas fa-clock"></i> Hourly Forecast
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="tab-pane" id="hourly-forecast">
                    <div class="hourly-date-selector">
                        <label for="hourly-date">Select Date:</label>
                        <select id="hourly-date">
                            <?php foreach ($forecast_data['forecast']['forecastday'] as $day): ?>
                                <option value="<?php echo $day['date']; ?>">
                                    <?php echo date('D, M j, Y', strtotime($day['date'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php foreach ($forecast_data['forecast']['forecastday'] as $index => $day): ?>
                        <div class="hourly-forecast-container" id="hourly-<?php echo $day['date']; ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>">
                            <h3><?php echo date('l, F j, Y', strtotime($day['date'])); ?></h3>
                            
                            <div class="hourly-forecast-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Condition</th>
                                            <th>Temp</th>
                                            <th>Feels Like</th>
                                            <th>Wind</th>
                                            <th>Humidity</th>
                                            <th>Precipitation</th>
                                            <th>Chance of Rain</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($day['hour'] as $hour): ?>
                                            <tr>
                                                <td><?php echo date('g:i A', strtotime($hour['time'])); ?></td>
                                                <td>
                                                    <i class="<?php echo getWeatherIcon($hour['condition']['code'], date('H', strtotime($hour['time'])) >= 6 && date('H', strtotime($hour['time'])) < 18); ?>"></i>
                                                    <?php echo $hour['condition']['text']; ?>
                                                </td>
                                                <td data-temp-c="<?php echo $hour['temp_c']; ?>" data-temp-f="<?php echo $hour['temp_f']; ?>">
                                                    <?php echo $unit === 'C' ? $hour['temp_c'] . '°C' : $hour['temp_f'] . '°F'; ?>
                                                </td>
                                                <td data-temp-c="<?php echo $hour['feelslike_c']; ?>" data-temp-f="<?php echo $hour['feelslike_f']; ?>">
                                                    <?php echo $unit === 'C' ? $hour['feelslike_c'] . '°C' : $hour['feelslike_f'] . '°F'; ?>
                                                </td>
                                                <td>
                                                    <?php echo $unit === 'C' ? $hour['wind_kph'] . ' km/h' : $hour['wind_mph'] . ' mph'; ?>
                                                    <?php echo $hour['wind_dir']; ?>
                                                </td>
                                                <td><?php echo $hour['humidity']; ?>%</td>
                                                <td><?php echo $unit === 'C' ? $hour['precip_mm'] . ' mm' : $hour['precip_in'] . ' in'; ?></td>
                                                <td><?php echo $hour['chance_of_rain']; ?>%</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="weather-alerts">
            <h3>Weather Alerts</h3>
            <?php 
                $alerts = isset($forecast_data['alerts']['alert']) ? $forecast_data['alerts']['alert'] : [];
                if (empty($alerts)):
            ?>
                <div class="no-alerts">
                    <i class="fas fa-check-circle"></i>
                    <p>No weather alerts for this location.</p>
                </div>
            <?php else: ?>
                <div class="alerts-list">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert-item">
                            <div class="alert-header">
                                <h4><?php echo $alert['headline']; ?></h4>
                                <span class="alert-severity"><?php echo $alert['severity']; ?></span>
                            </div>
                            <div class="alert-body">
                                <p><strong>Effective:</strong> <?php echo date('M j, Y g:i A', strtotime($alert['effective'])); ?></p>
                                <p><strong>Expires:</strong> <?php echo date('M j, Y g:i A', strtotime($alert['expires'])); ?></p>
                                <p><?php echo $alert['desc']; ?></p>
                                <?php if (!empty($alert['instruction'])): ?>
                                    <p><strong>Instructions:</strong> <?php echo $alert['instruction']; ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabPanes = document.querySelectorAll('.tab-pane');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all buttons and panes
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanes.forEach(pane => pane.classList.remove('active'));
                
                // Add active class to current button and pane
                this.classList.add('active');
                document.getElementById(tabId + '-forecast').classList.add('active');
            });
        });
        
        // Show hourly forecast buttons
        const hourlyButtons = document.querySelectorAll('.show-hourly');
        hourlyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const date = this.getAttribute('data-date');
                
                // Switch to hourly tab
                document.querySelector('.tab-btn[data-tab="hourly"]').click();
                
                // Select the date in the dropdown
                const dateSelect = document.getElementById('hourly-date');
                dateSelect.value = date;
                
                // Show the selected date's hourly forecast
                showHourlyForecast(date);
            });
        });
        
        // Hourly date selector
        const hourlyDateSelect = document.getElementById('hourly-date');
        if (hourlyDateSelect) {
            hourlyDateSelect.addEventListener('change', function() {
                showHourlyForecast(this.value);
            });
        }
        
        // Function to show hourly forecast for selected date
        function showHourlyForecast(date) {
            // Hide all hourly containers
            document.querySelectorAll('.hourly-forecast-container').forEach(container => {
                container.style.display = 'none';
            });
            
            // Show selected date's hourly container
            const selectedContainer = document.getElementById('hourly-' + date);
            if (selectedContainer) {
                selectedContainer.style.display = 'block';
            }
        }
    });
    
    // Function to change forecast days
    function changeForecastDays(days) {
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('days', days);
        window.location.href = currentUrl.toString();
    }
</script>

<?php include 'includes/footer.php'; ?> 