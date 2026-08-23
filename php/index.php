<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startUserSession();
$currentUserId = isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

$articleQuery = $pdo->query(
    'SELECT a.id, a.title, a.thumbnail, a.category, a.short_dec, a.article, a.date, u.email, u.dp
     FROM articles a JOIN users u ON u.id = a.userid ORDER BY a.date DESC'
);
$articles = $articleQuery->fetchAll();
$categories = $pdo->query(
    'SELECT category, COUNT(*) AS article_count FROM articles GROUP BY category ORDER BY article_count DESC'
)->fetchAll();

$articleCards = $articles ?: [null];
$latestHeaderArticles = [];
for ($i = 0; $i < 4; $i++) {
    $art = $articles[$i] ?? null;
    $latestHeaderArticles[] = [
        'id' => $art ? (int) $art['id'] : 0,
        'title' => articleValue($art, 'title', 'No articles published yet'),
        'description' => articleValue($art, 'short_dec', 'Create an article to see it here.'),
        'category' => articleValue($art, 'category', 'Technology'),
        'author' => articleValue($art, 'email', 'EntryBlog'),
        'date' => articleValue($art, 'date', ''),
        'thumbnail' => articleThumbnail($art),
    ];
}
function articleValue(?array $article, string $key, string $fallback): string
{
    $value = trim((string) ($article[$key] ?? ''));
    return escapeOutput($value !== '' ? $value : $fallback);
}
function articleThumbnail(?array $article): string
{
    $path = (string) ($article['thumbnail'] ?? '');
    return preg_match('/^uploads\/[a-f0-9]{32}\.(jpg|png|webp)$/', $path) ? escapeOutput('../' . $path) : '../assets/sample.png';
}
function articleAuthorDp(?array $article): string
{
    $path = (string) ($article['dp'] ?? '');
    return !empty($path) ? escapeOutput('../' . $path) : '../assets/sample-dp.png';
}
function renderArticleCard(?array $article, string $class): void
{
    $id = $article ? (int) $article['id'] : 0;
    $title = articleValue($article, 'title', 'No articles published yet');
    $description = articleValue($article, 'short_dec', 'Create an article to see it here.');
    $category = articleValue($article, 'category', 'Technology');
    $author = articleValue($article, 'email', 'EntryBlog');
    $date = articleValue($article, 'date', '');
    $thumbnail = articleThumbnail($article);
    $authorDp = articleAuthorDp($article);
    $click = $id > 0 ? " onclick=\"window.location.href='article.php?id={$id}'\"" : '';
    echo "<div class=\"{$class}\"{$click}>";
    echo '<div class="lable"><div class="dot"></div><p>Latest</p></div>';
    echo "<div class=\"post-image\"><img src=\"{$thumbnail}\" alt=\"Post image\"></div>";
    echo "<div class=\"category\"><p>{$category}</p></div><div class=\"topic\"><h2>{$title}</h2></div>";
    echo "<div class=\"short-description\"><h3>{$description}</h3></div>";
    echo "<section class=\"article-profile\"><div class=\"profile-dp\"><img src=\"{$authorDp}\" alt=\"Profile image\"></div>";
    echo "<div class=\"author-name\"><p>{$author}</p></div><div class=\"publish-date\"><p>{$date}</p></div><div class=\"reading-time\"><p>5 min read</p></div></section></div>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/index.css">
    <title>EntryBlog</title>
</head>

<body data-authenticated="<?php echo $currentUserId !== null ? 'true' : 'false'; ?>">
    <header>
        <div class="full-header">
            <section class="navigation">
                <div class="navbar">
                    <div class="logo"><a href="index.php"><img src="../assets/logo/logo.png" alt="Logo"></a></div>
                    <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false"><img
                            src="../assets/icons/profile-icon.png" alt="Menu"></button>
                    <div class="nav-links">
                        <ul>
                            <li class="active"><a href="index.php">Home</a></li>
                            <li><a href="#categories">Categories</a></li>
                            <li><a href="#latest-articles">Latest Articles</a></li>
                            <li><a href="#about">About us</a></li>
                            <li><a href="#contact">Contact</a></li>
                        </ul>
                    </div>
                    <div class="search-bar"><input type="text" placeholder="Search..."><img
                            src="../assets/icons/Search-white.png" alt="Search Icon"></div>
                </div>
            </section>
            <section>
                <div class="main-header">
                    <div class="social-icon">
                        <div class="line"></div>
                        <div class="social-links"><a href="#"><img src="../assets/icons/Facebook.png" alt="Facebook"></a><a
                                href="#"><img src="../assets/icons/Instagram Circle.png" alt="Instagram"></a><a
                                href="#"><img src="../assets/icons/Medium.png" alt="Medium"></a></div>
                    </div>
                    <div class="text-vector"><img src="../assets/texts/header h1.png" alt="EntryBlog">
                        <div class="header-button">
                            <a class="e-more" href="all-articles.php">
                                <p>Explore more...</p><img src="../assets/icons/Down Button.png" alt="Explore more">
                            </a>
                            <a class="s-down" href="#latest-articles">
                                <p>Scroll Down...</p><img src="../assets/icons/Down Button.png" alt="Scroll down">
                            </a>
                        </div>
                    </div>
                    <?php renderArticleCard($articleCards[0], 'hot-article'); ?>
                    <div class="number-line-main">
                        <p class="num-item active" data-index="0">1</p>
                        <div class="number-line"></div>
                        <p class="num-item" data-index="1">2</p>
                        <p class="num-item" data-index="2">3</p>
                        <p class="num-item" data-index="3">4</p>
                    </div>
                </div>
            </section>
        </div>
    </header>
    <section id="categories">
        <div class="section-2">
            <div class="category">
                <h3>explore by</h3>
                <h2>Categories</h2>
                <div class="category-list">
                    <?php foreach (array_slice($categories, 0, 4) as $category): ?>
                        <a class="category-item"
                            href="all-articles.php?category=<?php echo urlencode((string) $category['category']); ?>"><img
                                src="../assets/icons/Variant3-1.png"
                                alt="<?php echo escapeOutput((string) $category['category']); ?>">
                            <h2><?php echo escapeOutput((string) $category['category']); ?></h2>
                            <p><?php echo (int) $category['article_count']; ?> articles</p>
                        </a><?php endforeach; ?>
                    <?php if (!$categories): ?>
                        <div class="category-item">
                            <h2>No categories</h2>
                            <p>0 articles</p>
                        </div><?php endif; ?>
                </div>
                <a class="view-all-btn" href="all-articles.php">
                    <p>View all categories</p><img src="../assets/icons/Down Button.png" alt="View all">
                </a>
            </div>
            <div class="line"></div>
            <div class="article-cards">
                <div class="top-layer">
                    <h1>Editors' Picks</h1>
                    <div class="top-img">
                        <img src="../assets/icons/Down Button.png" alt="Previous">
                    </div>
                </div>
                <div class="articles">
                    <?php foreach (array_slice($articleCards, 0, 3) as $article):
                        renderArticleCard($article, 'editors-article');
                    endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    <section>
        <div class="middile-bar">
            <div class="profile-icon"><img src="../assets/icons/profile-icon.png" alt="Profile"></div>
            <div class="main-text">
                <p>Upload your own blog articles</p>
                <h1>to read everyone with us</h1>
            </div>
            <div class="second-text">
                <p>1000+ readers every month</p>
            </div>
            <div class="button"><a href="login.html">
                    <p>Login or Sign up</p><img src="../assets/icons/Down Button.png" alt="Login">
                </a></div>
        </div>
    </section>
    <section id="latest-articles">
        <div class="second-article">
            <div class="top">
                <div class="title">
                    <h1>latests from readers’</h1>
                    <h2>picks</h2>
                </div>
                <a class="btn" href="all-articles.php">
                    <p>View all</p><img src="../assets/icons/Down Button.png" alt="View all">
                </a>
            </div>
            <div class="item-container">
                <?php foreach (array_slice($articleCards, 0, 3) as $article): ?>
                    <div class="article-item" <?php if ($article): ?>
                            onclick="window.location.href='article.php?id=<?php echo (int) $article['id']; ?>'" <?php endif; ?>>
                        <div class="article-image"><img src="<?php echo articleThumbnail($article); ?>" alt="Post image">
                        </div>
                        <div class="caption">
                            <div class="category">
                                <p><?php echo articleValue($article, 'category', 'Technology'); ?></p>
                            </div>
                            <div class="topic">
                                <h2><?php echo articleValue($article, 'title', 'No articles published yet'); ?></h2>
                            </div>
                            <div class="short-description">
                                <h3><?php echo articleValue($article, 'short_dec', 'Create an article to see it here.'); ?>
                                </h3>
                            </div>
                            <section class="article-profile">
                                <div class="profile-dp"><img src="../assets/sample-dp.png" alt="Profile image"></div>
                                <div class="author-name">
                                    <h3><?php echo articleValue($article, 'email', 'EntryBlog'); ?></h3>
                                    <p><?php echo articleValue($article, 'date', ''); ?></p>
                                </div>
                                <div class="reading-time">
                                    <p>5 min read</p>
                                </div>
                            </section>
                        </div>
                    </div><?php endforeach; ?>
            </div>
        </div>
    </section>
    <section class="footer-1" id="contact">
        <footer>
            <div class="component">
                <div class="first"><img src="../assets/logo/logo.png" alt="Logo">
                    <div class="text-1">
                        <p>Upload your own blog articles</p>
                        <p class="bold">to read everyone with us</p>
                    </div>
                    <div class="copiright">
                        <p>© 2026 EntryBlog. All rights reserved.</p>
                    </div>
                </div>
                <div class="second"><img src="../assets/icons/Vector 2.png" alt="Decoration"></div>
                <div class="third">
                    <div class="page">
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="#categories">Categories</a></li>
                            <li><a href="#about">About us</a></li>
                            <li><a href="#contact">Contact</a></li>
                        </ul>
                    </div>
                    <div class="button">
                        <a class="b-2" href="login.html">
                            <p>Login or Sign up</p><img src="../assets/icons/Down Button.png" alt="Login">
                        </a>
                        <a class="b-1" href="all-articles.php">
                            <p>All Articles</p>
                            <img src="../assets/icons/Down Button.png" alt="All Articles">
                        </a>
                    </div>
                    <div class="social">
                        <p>Connect with us</p><img src="../assets/icons/Facebook.png" alt="Facebook"><img
                            src="../assets/icons/Instagram Circle.png" alt="Instagram"><img src="../assets/icons/Medium.png"
                            alt="Medium">
                    </div>
                </div>
            </div>
        </footer>
    </section>
    <script id="header-articles-data" type="application/json">
        <?php echo json_encode($latestHeaderArticles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dataEl = document.getElementById('header-articles-data');
            if (!dataEl) return;
            let headerArticles = [];
            try {
                headerArticles = JSON.parse(dataEl.textContent);
            } catch (e) {
                return;
            }

            const numberItems = document.querySelectorAll('.number-line-main .num-item');
            const hotCard = document.querySelector('.hot-article');
            if (!hotCard || !numberItems.length) return;

            const imgEl = hotCard.querySelector('.post-image img');
            const categoryEl = hotCard.querySelector('.category p');
            const titleEl = hotCard.querySelector('.topic h2');
            const descEl = hotCard.querySelector('.short-description h3');
            const authorEl = hotCard.querySelector('.author-name p');
            const dateEl = hotCard.querySelector('.publish-date p');

            function selectHeaderArticle(index) {
                const article = headerArticles[index];
                if (!article) return;

                numberItems.forEach((item, idx) => {
                    item.classList.toggle('active', idx === index);
                });

                if (imgEl && article.thumbnail) imgEl.src = article.thumbnail;
                if (categoryEl && article.category) categoryEl.textContent = article.category;
                if (titleEl && article.title) titleEl.textContent = article.title;
                if (descEl && article.description) descEl.textContent = article.description;
                if (authorEl && article.author) authorEl.textContent = article.author;
                if (dateEl) dateEl.textContent = article.date || '';

                if (article.id > 0) {
                    hotCard.onclick = () => { window.location.href = `article.php?id=${article.id}`; };
                    hotCard.style.cursor = 'pointer';
                } else {
                    hotCard.onclick = null;
                    hotCard.style.cursor = 'default';
                }
            }

            numberItems.forEach((item, index) => {
                item.addEventListener('click', () => {
                    selectHeaderArticle(index);
                });
            });
        });
    </script>
</body>
<script src="../js/navbar.js?v=<?php echo file_exists(__DIR__ . '/../js/navbar.js') ? filemtime(__DIR__ . '/../js/navbar.js') : time(); ?>"></script>

</html>