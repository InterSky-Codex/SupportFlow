CREATE TABLE ticket_attachments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    comment_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    storage_path VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY ticket_attachments_ticket_index (ticket_id),
    KEY ticket_attachments_comment_index (comment_id),
    KEY ticket_attachments_user_index (user_id),

    CONSTRAINT ticket_attachments_ticket_foreign
        FOREIGN KEY (ticket_id)
        REFERENCES tickets (id)
        ON DELETE CASCADE,

    CONSTRAINT ticket_attachments_comment_foreign
        FOREIGN KEY (comment_id)
        REFERENCES ticket_comments (id)
        ON DELETE CASCADE,

    CONSTRAINT ticket_attachments_user_foreign
        FOREIGN KEY (user_id)
        REFERENCES users (id)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
