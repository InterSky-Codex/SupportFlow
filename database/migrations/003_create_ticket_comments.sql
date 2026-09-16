CREATE TABLE ticket_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY ticket_comments_ticket_index (ticket_id),
    KEY ticket_comments_user_index (user_id),

    CONSTRAINT ticket_comments_ticket_foreign
        FOREIGN KEY (ticket_id)
        REFERENCES tickets (id)
        ON DELETE CASCADE,

    CONSTRAINT ticket_comments_user_foreign
        FOREIGN KEY (user_id)
        REFERENCES users (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
