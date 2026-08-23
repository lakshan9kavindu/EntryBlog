<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startUserSession();

function tableExists(PDO $pdo, string $table): bool
{
    $tableQuery = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table LIMIT 1'
    );
    $tableQuery->execute(['table' => $table]);
    return (bool) $tableQuery->fetchColumn();
}

function firstExistingTable(PDO $pdo, array $tables): ?string
{
    foreach ($tables as $table) {
        if (tableExists($pdo, $table)) {
            return $table;
        }
    }

    return null;
}

function userHasRow(PDO $pdo, string $table, int $userId, int $articleId): bool
{
    $rowQuery = $pdo->prepare("SELECT 1 FROM {$table} WHERE userid = :userid AND articleid = :articleid LIMIT 1");
    $rowQuery->execute(['userid' => $userId, 'articleid' => $articleId]);
    return (bool) $rowQuery->fetchColumn();
}

function toggleArticleRow(PDO $pdo, string $table, int $userId, int $articleId): void
{
    if (userHasRow($pdo, $table, $userId, $articleId)) {
        $deleteRow = $pdo->prepare("DELETE FROM {$table} WHERE userid = :userid AND articleid = :articleid");
        $deleteRow->execute(['userid' => $userId, 'articleid' => $articleId]);
        return;
    }

    $insertRow = $pdo->prepare("INSERT INTO {$table} (userid, articleid) VALUES (:userid, :articleid)");
    $insertRow->execute(['userid' => $userId, 'articleid' => $articleId]);
}

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

$currentUserId = isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$articleId = (int) $article['id'];
$articleAuthorId = (int) $article['userid'];
$saveTable = firstExistingTable($pdo, ['saved_articles', 'saves', 'saved', 'bookmarks']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''));

    $action = (string) ($_POST['action'] ?? '');
    $currentUserId = requireUser();

    if ($action === 'like') {
        toggleArticleRow($pdo, 'likes', $currentUserId, $articleId);
    } elseif ($action === 'follow' && $currentUserId !== $articleAuthorId) {
        $followQuery = $pdo->prepare('SELECT 1 FROM follows WHERE userid = :userid AND followid = :followid LIMIT 1');
        $followQuery->execute(['userid' => $currentUserId, 'followid' => $articleAuthorId]);

        if ($followQuery->fetchColumn()) {
            $deleteFollow = $pdo->prepare('DELETE FROM follows WHERE userid = :userid AND followid = :followid');
            $deleteFollow->execute(['userid' => $currentUserId, 'followid' => $articleAuthorId]);
        } else {
            $insertFollow = $pdo->prepare('INSERT INTO follows (userid, followid) VALUES (:userid, :followid)');
            $insertFollow->execute(['userid' => $currentUserId, 'followid' => $articleAuthorId]);
        }
    } elseif ($action === 'save' && $saveTable !== null) {
        toggleArticleRow($pdo, $saveTable, $currentUserId, $articleId);
    }

    header('Location: article.php?id=' . $articleId);
    exit;
}

if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === (int) $article['userid']) {
    header('Location: article-upload.php?edit=' . (int) $article['id']);
    exit;
}

$title = (string) ($article['title'] ?: 'Untitled article');
$category = (string) ($article['category'] ?: 'Technology');
$shortDescription = (string) ($article['short_dec'] ?: '');
$content = (string) $article['article'];
$thumbnail = !empty($article['thumbnail']) && preg_match('/^uploads\/[a-f0-9]{32}\.(jpg|png|webp)$/', (string) $article['thumbnail']) ? '../' . $article['thumbnail'] : '../assets/sample.png';
$author = (string) $article['email'];
$date = (string) $article['date'];

$likeCountQuery = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE articleid = :articleid');
$likeCountQuery->execute(['articleid' => $articleId]);
$likeCount = (int) $likeCountQuery->fetchColumn();

$followerCountQuery = $pdo->prepare('SELECT COUNT(*) FROM follows WHERE followid = :followid');
$followerCountQuery->execute(['followid' => $articleAuthorId]);
$followerCount = (int) $followerCountQuery->fetchColumn();

$isLiked = $currentUserId !== null && userHasRow($pdo, 'likes', $currentUserId, $articleId);
$isFollowed = false;
$isSaved = false;
$saveCount = 0;

if ($currentUserId !== null) {
    $followStateQuery = $pdo->prepare('SELECT 1 FROM follows WHERE userid = :userid AND followid = :followid LIMIT 1');
    $followStateQuery->execute(['userid' => $currentUserId, 'followid' => $articleAuthorId]);
    $isFollowed = (bool) $followStateQuery->fetchColumn();
}

if ($saveTable !== null) {
    $saveCountQuery = $pdo->prepare("SELECT COUNT(*) FROM {$saveTable} WHERE articleid = :articleid");
    $saveCountQuery->execute(['articleid' => $articleId]);
    $saveCount = (int) $saveCountQuery->fetchColumn();
    $isSaved = $currentUserId !== null && userHasRow($pdo, $saveTable, $currentUserId, $articleId);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/article.css">
    <title><?php echo escapeOutput($title); ?></title>
</head>

<body data-authenticated="<?php echo $currentUserId !== null ? 'true' : 'false'; ?>">
    <div class="full-header">
        <section class="navigation">
            <div class="navbar">
                <div class="logo"><a href="index.php"><img src="../assets/logo/logo.png" alt="Logo"></a></div>
                <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false"><img
                        src="../assets/icons/profile-icon.png" alt="Menu"></button>
                <div class="nav-links">
                    <ul>
                        <li class="active"><a href="index.php">Home</a></li>
                        <li><a href="index.php#categories">Categories</a></li>
                        <li><a href="index.php#about">About us</a></li>
                        <li><a href="index.php#contact">Contact</a></li>
                    </ul>
                </div>
                <div class="search-bar"><input type="text" placeholder="Search..."><img
                        src="../assets/icons/Search-white.png" alt="Search Icon"></div>
            </div>
        </section>
    </div>

    <div class="thumbnail-container">
        <div class="thumbnail">
            <div class="top">
                <div class="back-btn"><a href="index.php"><img src="../assets/icons/Scroll Down.png" alt="Back Button"></a>
                </div>
                <div class="image"><img src="<?php echo escapeOutput($thumbnail); ?>" alt="Article thumbnail"></div>
                <div class="save"><img src="../assets/icons/save-icon.png" alt="Save Icon"></div>
            </div>
        </div>
        <div class="middle">
            <div class="title">
                <h1><?php echo escapeOutput($title); ?></h1>
            </div>
            <div class="some-btn">
                <div class="btn"><img src="../assets/icons/like.png" alt="Like Icon">
                    <p><?php echo escapeOutput($category); ?></p>
                </div>
                <div class="btn"><img src="../assets/icons/like.png" alt="Like Icon">
                    <p><?php echo $likeCount; ?> Likes</p>
                </div>
                <div class="btn"><img src="../assets/icons/share.png" alt="Share Icon">
                    <p><?php echo $followerCount; ?> Followers</p>
                </div>
                <div class="btn"><img src="../assets/icons/save-icon.png" alt="Bookmark Icon">
                    <p><?php echo $saveCount; ?> Saved</p>
                </div>
            </div>
        </div>
    </div>

    <div class="artcle-content">
        <div class="content">
            <p class="article-short-description"><?php echo escapeOutput($shortDescription); ?></p>
            <p><?php echo nl2br(escapeOutput($content)); ?></p>
        </div>
        <div class="profile">
            <div class="profile-image"><img src="../assets/sample-dp.png" alt="Profile Image"></div>
            <div class="profile-info">
                <h3><?php echo escapeOutput($author); ?></h3>
                <p><?php echo escapeOutput($date); ?></p>
            </div>
            <form class="follow-btn" method="POST"><input type="hidden" name="csrf_token"
                    value="<?php echo escapeOutput(csrfToken()); ?>"><button type="submit" name="action"
                    value="follow"><?php echo $isFollowed ? 'Following' : 'Follow'; ?></button><img
                    src="../assets/icons/Vector 2.png" alt="Follow Icon"></form>
            <form class="like-btn" method="POST"><input type="hidden" name="csrf_token"
                    value="<?php echo escapeOutput(csrfToken()); ?>"><button type="submit" name="action"
                    value="like"><?php echo $isLiked ? 'Liked' : 'Like'; ?></button><img src="../assets/icons/Vector 2.png"
                    alt="Like Icon"></form>
            <form class="save-btn" method="POST"><input type="hidden" name="csrf_token"
                    value="<?php echo escapeOutput(csrfToken()); ?>"><button type="submit" name="action" value="save"
                    <?php echo $saveTable === null ? 'disabled' : ''; ?>><?php echo $isSaved ? 'Saved' : 'Save'; ?></button><img src="../assets/icons/Vector 2.png"
                    alt="Save Icon"></form>
        </div>
    </div>

    <section class="footer-1">
        <footer>
            <div class="component">
                <div class="first"><img src="../assets/logo/logo.png" alt="logo">
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
                            <li><a href="index.php#categories">Categories</a></li>
                            <li><a href="index.php#about">About us</a></li>
                            <li><a href="index.php#contact">Contact</a></li>
                        </ul>
                    </div>
                    <div class="button"><a class="b-2" href="login.html">
                            <p>Login or Sign up</p><img src="../assets/icons/Down Button.png" alt="Login or sign up">
                        </a></div>
                    <div class="social">
                        <p>Connect with us</p><img src="../assets/icons/facebook.png" alt="Facebook"><img
                            src="../assets/icons/Instagram Circle.png" alt="Instagram"><img src="../assets/icons/Medium.png"
                            alt="Medium">
                    </div>
                </div>
            </div>
        </footer>
    </section>
</body>
<script src="../js/navbar.js"></script>

</html>