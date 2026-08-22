<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = requireUser();

$userQuery = $pdo->prepare('SELECT id, email FROM users WHERE id = :id LIMIT 1');
$userQuery->execute(['id' => $userId]);
$user = $userQuery->fetch();

if (!$user) {
    header('Location: logout.php');
    exit;
}

$articleQuery = $pdo->prepare(
    'SELECT id, thumbnail, short_dec, article, date FROM articles WHERE userid = :userid ORDER BY date DESC'
);
$articleQuery->execute(['userid' => $userId]);
$articles = $articleQuery->fetchAll();
$displayEmail = escapeOutput((string) $user['email']);

$articleCards = $articles ?: [null, null];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile.css">
    <title>Profile</title>
</head>

<body>
    <header>
        <div class="full-header">
            <section class="navigation">
                <div class="navbar">
                    <div class="logo">
                        <img src="assets/logo/logo.png" alt="Logo">
                    </div>
                    <div class="nav-links">
                        <ul>
                            <li class="active"><a href="index.html">Home</a></li>
                            <li><a href="index.html">Categories</a></li>
                            <li><a href="index.html">About us</a></li>
                            <li><a href="index.html">Contact us</a></li>
                        </ul>
                    </div>
                    <div class="search-bar">
                        <input type="text" placeholder="Search...">
                        <img src="assets/icons/Search-white.png" alt="Search Icon">
                    </div>
                    <div class="user-profile">
                        <a href="logout.php"><img src="assets/icons/login-black.png" alt="Log out"></a>
                    </div>
                </div>
            </section>
        </div>
    </header>

    <section class="first-card">
        <div class="pro-card">
            <img src="assets/sample-dp.png" alt="Profile image">
            <h3><?php echo $displayEmail; ?></h3>
            <p>Member account</p>
            <div class="button">
                <a href="#"><img src="assets/icons/facebook.png" alt="followers"><p>Followers</p></a>
                <a href="#"><img src="assets/icons/Instagram Circle.png" alt="followings"><p>Following</p></a>
                <a href="#"><img src="assets/icons/Medium.png" alt="articles"><p><?php echo count($articles); ?> Articles</p></a>
            </div>
            <div class="button-2">
                <a href="article-upload.php"><h3>New article</h3><img src="assets/icons/facebook.png" alt="Add article"></a>
                <a href="logout.php"><h3>Log out</h3><img src="assets/icons/facebook.png" alt="Log out"></a>
                <a href="#"><h3>Edit profile</h3><img src="assets/icons/facebook.png" alt="Edit profile"></a>
            </div>
        </div>
        <div class="article-card">
            <div class="article-item">
                <?php foreach ($articleCards as $article):
                    $articleTitle = $article ? (string) ($article['short_dec'] ?: 'Untitled article') : 'Your articles will appear here';
                    $articleText = $article ? mb_substr((string) $article['article'], 0, 110) : 'Create a new article to see it on your profile.';
                    $articleDate = $article ? (string) $article['date'] : '';
                    $thumbnail = $article && !empty($article['thumbnail']) ? (string) $article['thumbnail'] : 'assets/sample.png';
                ?>
                <div class="editors-article"<?php if ($article): ?> onclick="window.location.href='article.php?id=<?php echo (int) $article['id']; ?>'"<?php endif; ?> >
                    <div class="lable"><div class="dot"></div><p>Latest</p></div>
                    <div class="post-image"><img src="<?php echo escapeOutput($thumbnail); ?>" alt="Post image"></div>
                    <div class="category"><p>Technology</p></div>
                    <div class="topic"><h2><?php echo escapeOutput($articleTitle); ?></h2></div>
                    <div class="short-description"><h3><?php echo escapeOutput($articleText); ?></h3></div>
                    <section class="article-profile">
                        <div class="profile-dp"><img src="assets/sample-dp.png" alt="Profile image"></div>
                        <div class="author-name"><p><?php echo $displayEmail; ?></p></div>
                        <div class="publish-date"><p><?php echo escapeOutput($articleDate); ?></p></div>
                        <div class="reading-time"><p>5 min read</p></div>
                    </section>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="view-articles-container">
                <a href="#" class="view-articles"><h3>Your Articles</h3><img src="assets/icons/Down Button.png" alt="Your articles"></a>
            </div>
        </div>
    </section>

    <section class="footer-1">
        <footer>
            <div class="component">
                <div class="first">
                    <img src="assets/logo/logo.png" alt="logo">
                    <div class="text-1">
                        <p>Upload your own blog articles</p>
                        <p class="bold">to read everyone with us</p>
                    </div>
                    <div class="copiright">
                        <p>© 2026 EntryBlog. All rights reserved.</p>
                    </div>
                </div>
                <div class="second">
                    <img src="assets/icons/Vector 2.png" alt="Decoration">
                </div>
                <div class="third">
                    <div class="page">
                        <ul>
                            <li><a href="index.html">Home</a></li>
                            <li><a href="index.html">Categories</a></li>
                            <li><a href="index.html">About us</a></li>
                            <li><a href="index.html">Contact us</a></li>
                        </ul>
                    </div>
                    <div class="button">
                        <div class="b-1">
                            <p>All articles</p>
                            <img src="assets/icons/Down Button.png" alt="Explore more">
                        </div>
                        <div class="b-2">
                            <p>Login or Sign up</p>
                            <img src="assets/icons/Down Button.png" alt="Explore more">
                        </div>
                    </div>
                    <div class="social">
                        <p>Connect with us</p>
                        <img src="assets/icons/facebook.png" alt="Facebook">
                        <img src="assets/icons/Instagram Circle.png" alt="Instagram">
                        <img src="assets/icons/Medium.png" alt="Medium">
                    </div>
                </div>
            </div>
        </footer>
    </section>
</body>

</html>
