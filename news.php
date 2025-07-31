<?php
$page_title = 'Weather News';
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/weather_api.php';

// Get user's default location
$location = null;

if (isLoggedIn()) {
    $user_details = getUserDetails($_SESSION['user_id']);
    if ($user_details['success'] && !empty($user_details['user']['default_location'])) {
        $location = $user_details['user']['default_location'];
    }
}

// Check if specific news article is requested
$single_news = false;
$current_news = null;

if (isset($_GET['id'])) {
    $news_id = $_GET['id'];
    $all_news = getWeatherNews($location);
    
    foreach ($all_news as $news) {
        if (md5($news['title']) === $news_id) {
            $single_news = true;
            $current_news = $news;
            break;
        }
    }
}

// Get all news if not viewing a single article
if (!$single_news) {
    $all_news = getWeatherNews($location);
}

// Include header
include 'includes/header.php';
?>

<div class="news-page">
    <div class="page-header">
        <h1>Weather News</h1>
        <?php if (!$single_news): ?>
            <div class="news-filters">
                <form class="search-box">
                    <input type="text" name="search" placeholder="Search news..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if ($single_news && $current_news): ?>
        <div class="news-article">
            <div class="news-article-header">
                <h2><?php echo $current_news['title']; ?></h2>
                <div class="news-meta">
                    <span class="news-date"><i class="far fa-calendar-alt"></i> <?php echo $current_news['date']; ?></span>
                    <span class="news-source"><i class="far fa-newspaper"></i> <?php echo $current_news['source']; ?></span>
                </div>
            </div>
            
            <div class="news-article-image">
                <img src="<?php echo $current_news['image']; ?>" alt="<?php echo $current_news['title']; ?>">
            </div>
            
            <div class="news-article-content">
                <p><?php echo $current_news['description']; ?></p>
                
                <!-- Simulated full article content -->
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget aliquam nisl nisl sit amet nisl. Nullam auctor, nisl eget ultricies tincidunt, nisl nisl aliquam nisl, eget aliquam nisl nisl sit amet nisl.</p>
                
                <p>Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
                
                <p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>
                
                <p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.</p>
            </div>
            
            <div class="news-article-footer">
                <a href="news.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to News</a>
            </div>
        </div>
    <?php else: ?>
        <?php
        // Filter news if search is provided
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search = strtolower($_GET['search']);
            $filtered_news = [];
            
            foreach ($all_news as $news) {
                if (strpos(strtolower($news['title']), $search) !== false || 
                    strpos(strtolower($news['description']), $search) !== false) {
                    $filtered_news[] = $news;
                }
            }
            
            $all_news = $filtered_news;
        }
        ?>
        
        <?php if (empty($all_news)): ?>
            <div class="no-news">
                <i class="far fa-newspaper"></i>
                <p>No news articles found. Please try a different search term.</p>
                <a href="news.php" class="btn btn-primary">Clear Search</a>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($all_news as $news): ?>
                    <div class="card news-card">
                        <div class="news-image">
                            <img src="<?php echo $news['image']; ?>" alt="<?php echo $news['title']; ?>">
                        </div>
                        <div class="news-content">
                            <h3 class="news-title">
                                <a href="news.php?id=<?php echo md5($news['title']); ?>"><?php echo $news['title']; ?></a>
                            </h3>
                            <p class="news-description"><?php echo $news['description']; ?></p>
                            <div class="news-meta">
                                <span class="news-date"><i class="far fa-calendar-alt"></i> <?php echo $news['date']; ?></span>
                                <span class="news-source"><i class="far fa-newspaper"></i> <?php echo $news['source']; ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?> 