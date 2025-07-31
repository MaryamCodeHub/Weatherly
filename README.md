# Weatherly
A sleek, responsive weather dashboard that provides current weather conditions, 24-hour and 5-day forecasts, and **live weather news updates** powered by NewsAPI. The application is styled using modern CSS with animated backgrounds based on real-time weather conditions.



##  Features

-  Display of current location and weather data (temperature, condition, humidity)
-  Hourly and 5-day forecast views
-  Responsive world map (optional for future enhancements)
-  **Live Weather News** section with:
  - Real-time articles fetched via NewsAPI
  - Infinite scrolling for seamless user experience
  - Interactive buttons to read full articles
- Sidebar navigation with "Dashboard", "Location", and "Reading"



##  Technologies Used

- HTML5, CSS3 (Custom Styles)
- JavaScript (Vanilla)
- [NewsAPI](https://newsapi.org) for live weather-related news


##  Setup Instructions
1. Clone the Repository
        git clone https://github.com/your-username/weather-dashboard.git
        cd weather-dashboard

2. Get a Free API Key from NewsAPI
Sign up at https://newsapi.org
Copy your API key

3. Insert Your API Key
Open index.html and replace the placeholder with your key:
      const response = await fetch(`https://newsapi.org/v2/everything?q=weather&language=en&page=${page}&apiKey=YOUR_NEWS_API_KEY`);
Replace YOUR_NEWS_API_KEY with your actual key.
