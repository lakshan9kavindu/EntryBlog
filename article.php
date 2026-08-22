<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startUserSession();

$requestedId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1],
]);

if ($requestedId === false || $requestedId === null) {
    $articleQuery = $pdo->query(
        'SELECT a.id, a.userid, a.title, a.thumbnail, a.category, a.short_dec, a.article, a.date, u.email FROM articles a JOIN users u ON u.id = a.userid ORDER BY a.date DESC LIMIT 1'
    );
} else {
    $articleQuery = $pdo->prepare(
        'SELECT a.id, a.userid, a.title, a.thumbnail, a.category, a.short_dec, a.article, a.date, u.email FROM articles a JOIN users u ON u.id = a.userid WHERE a.id = :id LIMIT 1'
    );
    $articleQuery->execute(['id' => $requestedId]);
}

$article = $articleQuery->fetch();

if (!$article) {
    http_response_code(404);
    exit('Article not found.');
}

if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === (int) $article['userid']) {
    header('Location: article-upload.php?edit=' . (int) $article['id']);
    exit;
}

$title = (string) ($article['title'] ?: 'Untitled article');
$category = (string) ($article['category'] ?: 'Technology');
$shortDescription = (string) ($article['short_dec'] ?: '');
$content = (string) $article['article'];
$thumbnail = (string) ($article['thumbnail'] ?: 'assets/sample.png');
$author = (string) $article['email'];
$date = (string) $article['date'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="article.css">
    <title><?php echo escapeOutput($title); ?></title>
</head>
<body>
    <div class="full-header"><section class="navigation"><div class="navbar">
        <div class="logo"><img src="assets/logo/logo.png" alt="Logo"></div>
        <div class="nav-links"><ul>
            <li class="active"><a href="index.php">Home</a></li><li><a href="index.php#categories">Categories</a></li><li><a href="index.php#about">About us</a></li><li><a href="index.php#contact">Contact</a></li>
        </ul></div>
        <div class="search-bar"><input type="text" placeholder="Search..."><img src="assets/icons/Search-white.png" alt="Search Icon"></div>
        <div class="user-profile"><img src="assets/icons/login-black.png" alt="User Icon"></div>
    </div></section></div>

    <div class="thumbnail-container"><div class="thumbnail"><div class="top">
        <div class="back-btn"><a href="index.php"><img src="assets/icons/Scroll Down.png" alt="Back Button"></a></div>
        <div class="image"><img src="<?php echo escapeOutput($thumbnail); ?>" alt="Article thumbnail"></div>
        <div class="save"><img src="assets/icons/save-icon.png" alt="Save Icon"></div>
    </div></div>
    <div class="middle"><div class="title"><h1><?php echo escapeOutput($title); ?></h1></div>
        <div class="some-btn"><div class="btn"><img src="assets/icons/like.png" alt="Like Icon"><p><?php echo escapeOutput($category); ?></p></div><div class="btn"><img src="assets/icons/like.png" alt="Like Icon"><p>Likes 20</p></div><div class="btn"><img src="assets/icons/share.png" alt="Share Icon"><p>Minutes 3</p></div><div class="btn"><img src="assets/icons/save-icon.png" alt="Bookmark Icon"><p>Saved</p></div></div>
    </div></div>

    <div class="artcle-content"><div class="content"><p class="article-short-description"><?php echo escapeOutput($shortDescription); ?></p><p><?php echo nl2br(escapeOutput($content)); ?></p></div>
        <div class="profile"><div class="profile-image"><img src="assets/sample-dp.png" alt="Profile Image"></div><div class="profile-info"><h3><?php echo escapeOutput($author); ?></h3><p><?php echo escapeOutput($date); ?></p></div><div class="follow-btn"><button>Follow</button><img src="assets/icons/Vector 2.png" alt="Follow Icon"></div><div class="like-btn"><button>Like</button><img src="assets/icons/Vector 2.png" alt="Like Icon"></div><div class="save-btn"><button>Save</button><img src="assets/icons/Vector 2.png" alt="Save Icon"></div></div>
    </div>

    <section class="footer-1"><footer><div class="component"><div class="first"><img src="assets/logo/logo.png" alt="logo"><div class="text-1"><p>Upload your own blog articles</p><p class="bold">to read everyone with us</p></div><div class="copiright"><p>© 2026 EntryBlog. All rights reserved.</p></div></div><div class="second"><img src="assets/icons/Vector 2.png" alt="Decoration"></div><div class="third"><div class="page"><ul><li><a href="index.php">Home</a></li><li><a href="index.php#categories">Categories</a></li><li><a href="index.php#about">About us</a></li><li><a href="index.php#contact">Contact</a></li></ul></div><div class="button"><div class="b-2"><p>Login or Sign up</p><img src="assets/icons/Down Button.png" alt="Login or sign up"></div></div><div class="social"><p>Connect with us</p><img src="assets/icons/facebook.png" alt="Facebook"><img src="assets/icons/Instagram Circle.png" alt="Instagram"><img src="assets/icons/Medium.png" alt="Medium"></div></div></div></footer></section>
</body>
</html>
