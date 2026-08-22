CREATE DATABASE blog_system;
USE blog_system;

-- Users
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Articles
CREATE TABLE articles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(500),
    category VARCHAR(100) NOT NULL DEFAULT 'Technology',
    userid INT UNSIGNED NOT NULL,
    short_dec TEXT,
    article TEXT NOT NULL,
    date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (userid) REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- Likes
CREATE TABLE likes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userid INT UNSIGNED NOT NULL,
    articleid INT UNSIGNED NOT NULL,

    FOREIGN KEY (userid) REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (articleid) REFERENCES articles(id)
        ON DELETE CASCADE,

    -- Prevent the same user from liking an article twice
    UNIQUE (userid, articleid)
);

-- Follows
CREATE TABLE follows (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userid INT UNSIGNED NOT NULL,
    followid INT UNSIGNED NOT NULL,

    FOREIGN KEY (userid) REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (followid) REFERENCES users(id)
        ON DELETE CASCADE,

    -- Prevent following the same person twice
    UNIQUE (userid, followid),

    -- Prevent a user from following themselves
    CHECK (userid <> followid)
);