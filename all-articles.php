<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

startUserSession();

$category = trim((string) ($_GET['category'] ?? ''));
$ownerOnly = ($_GET['owner'] ?? '') === '1';
$userId = isset($_SESSION['user_id']) && is_int($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

if ($ownerOnly) {
    $userId = requireUser();
}

$conditions = [];
$parameters = [];
if ($category !== '') {
    $conditions[] = 'a.category = :category';
    $parameters['category'] = $category;
}
if ($ownerOnly) {
    $conditions[] = 'a.userid = :userid';
    $parameters['userid'] = $userId;
}

$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$articleQuery = $pdo->prepare(
    'SELECT a.id, a.title, a.thumbnail, a.category, a.short_dec, a.article, a.date, u.email
     FROM articles a JOIN users u ON u.id = a.userid' . $where . ' ORDER BY a.date DESC'
);
$articleQuery->execute($parameters);
$articles = $articleQuery->fetchAll();
$pageTitle = $ownerOnly ? 'Your Articles' : ($category !== '' ? $category . ' Articles' : 'All Articles');

function allArticleValue(array $article, string $key, string $fallback = ''): string
{
    $value = trim((string) ($article[$key] ?? ''));
    return escapeOutput($value !== '' ? $value : $fallback);
}

function allArticleThumbnail(array $article): string
{
    $path = (string) ($article['thumbnail'] ?? '');
    return preg_match('/^uploads\/[a-f0-9]{32}\.(jpg|png|webp)$/', $path) ? escapeOutput($path) : 'assets/sample.png';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="index.css">
    <title><?php echo escapeOutput($pageTitle); ?></title>
    <style>
        .all-articles-page {
            width: min(calc(100% - 40px), 1180px);
            margin: 55px auto 90px;
        }

        .all-articles-heading {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .all-articles-heading h1 {
            margin: 0;
            color: #040506;
            font-size: 42px;
        }

        .all-articles-heading p {
            margin: 0;
            color: #777;
            font-size: 14px;
        }

        .all-articles-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .all-articles-grid .editors-article {
            width: 100%;
            height: 390px;
            cursor: pointer;
        }

        .all-articles-empty {
            padding: 50px;
            border-radius: 20px;
            background: #f7f5f4;
            color: #777;
        }

        @media (max-width: 850px) {
            .all-articles-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .all-articles-page {
                width: min(calc(100% - 28px), 620px);
                margin: 30px auto 50px;
            }

            .all-articles-heading {
                display: block;
            }

            .all-articles-heading h1 {
                font-size: 28px;
                margin-bottom: 8px;
            }

            .all-articles-grid {
                grid-template-columns: 1fr;
            }

            .all-articles-grid .editors-article {
                height: 360px;
            }
        }

        @media (max-width: 420px) {
            .all-articles-heading h1 {
                font-size: 24px;
            }

            .all-articles-empty {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body data-authenticated="<?php echo $userId !== null ? 'true' : 'false'; ?>">
    <header>
        <div class="full-header">
            <section class="navigation">
                <div class="navbar">
                    <div class="logo"><a href="index.php"><img src="assets/logo/logo.png" alt="Logo"></a></div>
                    <button class="menu-toggle" type="button" aria-label="Open navigation" aria-expanded="false"><img src="assets/icons/profile-icon.png" alt="Menu"></button>
                    <div class="nav-links">
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li class="active"><a href="all-articles.php">Categories</a></li>
                            <li><a href="index.php#about">About us</a></li>
                            <li><a href="index.php#contact">Contact</a></li>
                        </ul>
                    </div>
                    <div class="search-bar"><input type="text" placeholder="Search..."><img
                            src="assets/icons/Search-white.png" alt="Search Icon"></div>
                    <div class="user-profile" data-hide-when-authenticated="true"><a href="login.html"><img src="assets/icons/login-black.png"
                                alt="Login"></a></div>
                </div>
            </section>
        </div>
    </header>
    <main class="all-articles-page">
        <div class="all-articles-heading">
            <h1><?php echo escapeOutput($pageTitle); ?></h1>
            <p><?php echo count($articles); ?> article<?php echo count($articles) === 1 ? '' : 's'; ?></p>
        </div>
        <?php if (!$articles): ?>
            <p class="all-articles-empty">No articles found.</p>
        <?php else: ?>
            <div class="all-articles-grid">
                <?php foreach ($articles as $article): ?>
                    <article class="editors-article"
                        onclick="window.location.href='article.php?id=<?php echo (int) $article['id']; ?>'">
                        <div class="lable">
                            <div class="dot"></div>
                            <p>Latest</p>
                        </div>
                        <div class="post-image"><img src="<?php echo allArticleThumbnail($article); ?>" alt="Article thumbnail">
                        </div>
                        <div class="category">
                            <p><?php echo allArticleValue($article, 'category', 'Technology'); ?></p>
                        </div>
                        <div class="topic">
                            <h2><?php echo allArticleValue($article, 'title', 'Untitled article'); ?></h2>
                        </div>
                        <div class="short-description">
                            <h3><?php echo allArticleValue($article, 'short_dec', ''); ?></h3>
                        </div>
                        <section class="article-profile">
                            <div class="profile-dp"><img src="assets/sample-dp.png" alt="Profile image"></div>
                            <div class="author-name">
                                <p><?php echo allArticleValue($article, 'email', 'EntryBlog'); ?></p>
                            </div>
                            <div class="publish-date">
                                <p><?php echo allArticleValue($article, 'date'); ?></p>
                            </div>
                            <div class="reading-time">
                                <p>5 min read</p>
                            </div>
                        </section>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <section class="footer-1" id="contact">
        <footer>
            <div class="component">
                <div class="first"><img src="assets/logo/logo.png" alt="Logo">
                    <div class="text-1">
                        <p>Upload your own blog articles</p>
                        <p class="bold">to read everyone with us</p>
                    </div>
                    <div class="copiright">
                        <p>© 2026 EntryBlog. All rights reserved.</p>
                    </div>
                </div>
                <div class="second"><img src="assets/icons/Vector 2.png" alt="Decoration"></div>
                <div class="third">
                    <div class="page">
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="all-articles.php">Categories</a></li>
                            <li><a href="index.php#about">About us</a></li>
                            <li><a href="index.php#contact">Contact</a></li>
                        </ul>
                    </div>
                    <div class="button">
                        <a class="b-2" href="login.html">
                            <p>Login or Sign up</p><img src="assets/icons/Down Button.png" alt="Login">
                        </a>
                    </div>
                    <div class="social">
                        <p>Connect with us</p><img src="assets/icons/facebook.png" alt="Facebook"><img
                            src="assets/icons/Instagram Circle.png" alt="Instagram"><img src="assets/icons/Medium.png"
                            alt="Medium">
                    </div>
                </div>
            </div>
        </footer>
    </section>
</body>
<script src="navbar.js"></script>

</html>
