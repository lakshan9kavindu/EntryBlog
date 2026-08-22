<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$userId = requireUser();
startUserSession();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken((string) ($_POST['csrf_token'] ?? ''));

    $title = trim((string) ($_POST['title'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $shortDescription = trim((string) ($_POST['short_description'] ?? ''));
    $article = trim((string) ($_POST['article'] ?? ''));
    $thumbnail = $_FILES['thumbnail'] ?? null;
    $allowedCategories = ['Technology', 'Health', 'Travel', 'Food', 'Business'];

    if ($title === '' || strlen($title) > 255) {
        $errorMessage = 'Please enter a title of 255 characters or fewer.';
    } elseif (!in_array($category, $allowedCategories, true)) {
        $errorMessage = 'Please choose a valid category.';
    } elseif ($shortDescription === '' || strlen($shortDescription) > 500) {
        $errorMessage = 'Please enter a short description of 500 characters or fewer.';
    } elseif ($article === '' || strlen($article) < 20 || strlen($article) > 1000000) {
        $errorMessage = 'Article content must be between 20 and 1,000,000 characters.';
    } elseif (!$thumbnail || $thumbnail['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Please choose an image thumbnail.';
    } elseif ($thumbnail['size'] > 5 * 1024 * 1024) {
        $errorMessage = 'The thumbnail must be 5 MB or smaller.';
    } else {
        $imageInfo = @getimagesize($thumbnail['tmp_name']);
        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($thumbnail['tmp_name']);
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $isSixteenByNine = $imageInfo && abs(($imageInfo[0] / $imageInfo[1]) - (16 / 9)) < 0.08;

        if (!$imageInfo || !isset($allowedTypes[$mimeType]) || !$isSixteenByNine) {
            $errorMessage = 'Please upload a JPG, PNG, or WebP image with a 16:9 ratio.';
        } else {
            $uploadDirectory = __DIR__ . '/uploads';
            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0750, true);
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mimeType];
            $storedPath = $uploadDirectory . '/' . $fileName;
            $databasePath = 'uploads/' . $fileName;

            if (!move_uploaded_file($thumbnail['tmp_name'], $storedPath)) {
                $errorMessage = 'The thumbnail could not be saved.';
            } else {
                $createArticle = $pdo->prepare(
                    'INSERT INTO articles (title, thumbnail, category, userid, short_dec, article) VALUES (:title, :thumbnail, :category, :userid, :short_dec, :article)'
                );
                $createArticle->execute([
                    'title' => $title,
                    'thumbnail' => $databasePath,
                    'category' => $category,
                    'userid' => $userId,
                    'short_dec' => $shortDescription,
                    'article' => $article,
                ]);

                header('Location: profile.php?created=1');
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="article-upload.css">
    <title>Upload Article</title>
</head>
<body>
    <div class="full-header">
        <section class="navigation">
            <div class="navbar">
                <div class="logo"><img src="assets/logo/logo.png" alt="Logo"></div>
                <div class="nav-links"><ul>
                    <li class="active"><a href="index.php">Home</a></li>
                    <li><a href="index.php#categories">Categories</a></li>
                    <li><a href="index.php#about">About us</a></li>
                    <li><a href="index.php#contact">Contact us</a></li>
                </ul></div>
                <div class="search-bar">
                    <input type="text" placeholder="Search...">
                    <img src="assets/icons/Search-white.png" alt="Search Icon">
                </div>
                <div class="user-profile"><a href="logout.php"><img src="assets/icons/login-black.png" alt="Log out"></a></div>
            </div>
        </section>
    </div>

    <main class="upload-page">
        <?php if ($errorMessage !== ''): ?><p class="upload-message error"><?php echo escapeOutput($errorMessage); ?></p><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo escapeOutput(csrfToken()); ?>">
            <div class="category-bar">
                <strong>CATEGORY</strong><span class="category-arrow">&#8250;</span>
                <label class="visually-hidden" for="category-input">Category</label>
                <select id="category-input" name="category" required>
                    <option value="">Select category</option>
                    <?php foreach (['Technology', 'Health', 'Travel', 'Food', 'Business'] as $categoryOption): ?>
                        <option value="<?php echo escapeOutput($categoryOption); ?>" <?php echo (($_POST['category'] ?? '') === $categoryOption) ? 'selected' : ''; ?>><?php echo escapeOutput($categoryOption); ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="category-divider">|</span><span class="category-label">Article category</span>
            </div>
            <section class="upload-intro">
                <div class="article-fields">
                    <label for="title-input">Article title</label>
                    <input id="title-input" name="title" type="text" maxlength="255" value="<?php echo escapeOutput((string) ($_POST['title'] ?? '')); ?>" placeholder="Enter article title" required>
                    <label for="short-description-input">Short description</label>
                    <textarea id="short-description-input" name="short_description" maxlength="500" placeholder="Describe your article briefly" required><?php echo escapeOutput((string) ($_POST['short_description'] ?? '')); ?></textarea>
                    <h1>UPLOAD HERE TO YOUR<br>ARTICLE THUMBNAIL<br>ACCEPT RATION WITH<br>16:9</h1>
                </div>
                <span class="intro-arrow">&#8250;</span>
                <label class="thumbnail-upload" for="thumbnail-input">
                    <span class="upload-placeholder" aria-hidden="true">&#9673;</span>
                    <img class="thumbnail-preview" alt="Selected thumbnail preview" hidden>
                    <input id="thumbnail-input" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp" required>
                </label>
            </section>
            <section class="editor" aria-label="Article content editor">
                <div class="editor-toolbar">
                    <span>ADD YOUR CONTENT HERE AND USE THOSE TOOLS TO STYLISE</span>
                    <button type="button" aria-label="Bold"><strong>B</strong></button><button type="button" aria-label="Italic"><em>I</em></button><button type="button" aria-label="List">&#8801;</button>
                </div>
                <textarea name="article" placeholder="type here..." aria-label="Article content" required><?php echo escapeOutput((string) ($_POST['article'] ?? '')); ?></textarea>
            </section>
            <button class="submit-article" type="submit">Submit article <span>&#8250;</span></button>
        </form>
    </main>
    <script>
        const input = document.querySelector('#thumbnail-input');
        const preview = document.querySelector('.thumbnail-preview');
        const placeholder = document.querySelector('.upload-placeholder');
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;
            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
            placeholder.hidden = true;
        });
    </script>
</body>
</html>
