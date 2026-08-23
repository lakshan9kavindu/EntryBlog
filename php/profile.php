<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = requireUser();

$userQuery = $pdo->prepare('SELECT id, email, dp FROM users WHERE id = :id LIMIT 1');
$userQuery->execute(['id' => $userId]);
$user = $userQuery->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$userDp = !empty($user['dp']) ? '../' . $user['dp'] : '../assets/sample-dp.png';
$articleQuery = $pdo->prepare(
    'SELECT id, title, thumbnail, category, short_dec, article, date FROM articles WHERE userid = :userid ORDER BY date DESC'
);
$articleQuery->execute(['userid' => $userId]);
$articles = $articleQuery->fetchAll();
$displayEmail = escapeOutput((string) $user['email']);

$followersQuery = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE followid = :userid');
$followersQuery->execute(['userid' => $userId]);
$followersCount = (int) $followersQuery->fetchColumn();

$followingQuery = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE userid = :userid');
$followingQuery->execute(['userid' => $userId]);
$followingCount = (int) $followingQuery->fetchColumn();

$articleCards = array_slice($articles, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/profile.css">
    <title>Profile - EntryBlog</title>
</head>

<body data-authenticated="true">
    <header>
        <div class="full-header">
            <section class="navigation">
                <div class="navbar">
                    <div class="logo">
                        <a href="index.php"><img src="../assets/logo/logo.png" alt="Logo"></a>
                    </div>
                    <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false"><img
                            src="../assets/icons/profile-icon.png" alt="Menu"></button>
                    <div class="nav-links">
                        <ul>
                            <li class="active"><a href="index.php">Home</a></li>
                            <li><a href="index.php#categories">Categories</a></li>
                            <li><a href="index.php#about">About us</a></li>
                            <li><a href="index.php#contact">Contact us</a></li>
                        </ul>
                    </div>
                    <div class="search-bar">
                        <input type="text" placeholder="Search...">
                        <img src="../assets/icons/Search-white.png" alt="Search Icon">
                    </div>
                </div>
            </section>
        </div>
    </header>

    <section class="first-card">
        <div class="pro-card">
            <img src="<?php echo escapeOutput($userDp); ?>" alt="Profile image">
            <h3><?php echo $displayEmail; ?></h3>
            <p>Member account</p>
            <div class="button">
                <a href="#"><img src="../assets/icons/Facebook.png" alt="followers">
                    <p><?php echo $followersCount; ?> Followers</p>
                </a>
                <a href="#"><img src="../assets/icons/Instagram Circle.png" alt="followings">
                    <p><?php echo $followingCount; ?> Following</p>
                </a>
                <a href="#"><img src="../assets/icons/Medium.png" alt="articles">
                    <p><?php echo count($articles); ?> Articles</p>
                </a>
            </div>
            <div class="button-2">
                <a href="article-upload.php">
                    <h3>New article</h3><img src="../assets/icons/Facebook.png" alt="Add article">
                </a>
                <a href="logout.php">
                    <h3>Log out</h3><img src="../assets/icons/Facebook.png" alt="Log out">
                </a>
            </div>
        </div>
        <div class="article-card">
            <div class="article-item">
                <?php foreach ($articleCards as $article):
                    $articleTitle = $article ? (string) ($article['title'] ?: 'Untitled article') : 'Your articles will appear here';
                    $articleCategory = $article ? (string) ($article['category'] ?: 'Technology') : 'Technology';
                    $articleText = $article ? mb_substr((string) $article['article'], 0, 110) : 'Create a new article to see it on your profile.';
                    $articleDate = $article ? (string) $article['date'] : '';
                    $thumbnail = $article && !empty($article['thumbnail']) && preg_match('/^uploads\/[a-f0-9]{32}\.(jpg|png|webp)$/', (string) $article['thumbnail']) ? '../' . $article['thumbnail'] : '../assets/sample.png';
                    ?>
                    <div class="editors-article" <?php if ($article): ?>
                            onclick="window.location.href='article.php?id=<?php echo (int) $article['id']; ?>'" <?php endif; ?>>
                        <div class="lable">
                            <div class="dot"></div>
                            <p>Latest</p>
                        </div>
                        <div class="post-image"><img src="<?php echo escapeOutput($thumbnail); ?>" alt="Post image"></div>
                        <div class="category">
                            <p><?php echo escapeOutput($articleCategory); ?></p>
                        </div>
                        <div class="topic">
                            <h2><?php echo escapeOutput($articleTitle); ?></h2>
                        </div>
                        <div class="short-description">
                            <h3><?php echo escapeOutput($articleText); ?></h3>
                        </div>
                        <section class="article-profile">
                            <div class="profile-dp"><img src="<?php echo escapeOutput($userDp); ?>" alt="Profile image"></div>
                            <div class="author-name">
                                <p><?php echo $displayEmail; ?></p>
                            </div>
                            <div class="publish-date">
                                <p><?php echo escapeOutput($articleDate); ?></p>
                            </div>
                            <div class="reading-time">
                                <p>5 min read</p>
                            </div>
                        </section>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="view-articles-container">
                <a href="all-articles.php?owner=1" class="view-articles">
                    <h3>Your Articles</h3><img src="../assets/icons/Down Button.png" alt="Your articles">
                </a>
            </div>
        </div>
    </section>

    <section class="footer-1">
        <footer>
            <div class="component">
                <div class="first">
                    <img src="../assets/logo/logo.png" alt="Logo">
                    <div class="text-1">
                        <p>Upload your own blog articles</p>
                        <p class="bold">to read everyone with us</p>
                    </div>
                    <div class="copiright">
                        <p>© 2026 EntryBlog. All rights reserved.</p>
                    </div>
                </div>
                <div class="second">
                    <img src="../assets/icons/Vector 2.png" alt="Decoration">
                </div>
                <div class="third">
                    <div class="page">
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="index.php#categories">Categories</a></li>
                            <li><a href="index.php#about">About us</a></li>
                            <li><a href="index.php#contact">Contact us</a></li>
                        </ul>
                    </div>
                    <div class="button">
                        <a class="b-1" href="all-articles.php">
                            <p>All articles</p>
                            <img src="../assets/icons/Down Button.png" alt="All articles">
                        </a>
                        <a class="b-2" href="logout.php">
                            <p>Log out</p>
                            <img src="../assets/icons/Down Button.png" alt="Log out">
                        </a>
                    </div>
                    <div class="social">
                        <p>Connect with us</p>
                        <img src="../assets/icons/Facebook.png" alt="Facebook">
                        <img src="../assets/icons/Instagram Circle.png" alt="Instagram">
                        <img src="../assets/icons/Medium.png" alt="Medium">
                    </div>
                </div>
            </div>
        </footer>
    </section>
</body>
<script src="../js/navbar.js?v=<?php echo file_exists(__DIR__ . '/../js/navbar.js') ? filemtime(__DIR__ . '/../js/navbar.js') : time(); ?>"></script>

</html>