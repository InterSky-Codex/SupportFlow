CREATE TABLE knowledge_articles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    category VARCHAR(80) NOT NULL DEFAULT 'general',
    summary VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    view_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_by BIGINT UNSIGNED NOT NULL,
    updated_by BIGINT UNSIGNED NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    KEY knowledge_articles_status_created_idx (status, created_at),
    KEY knowledge_articles_category_created_idx (category, created_at),
    KEY knowledge_articles_slug_idx (slug),
    KEY knowledge_articles_published_idx (status, published_at),

    CONSTRAINT knowledge_articles_created_by_foreign
        FOREIGN KEY (created_by)
        REFERENCES users (id)
        ON DELETE RESTRICT,

    CONSTRAINT knowledge_articles_updated_by_foreign
        FOREIGN KEY (updated_by)
        REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
