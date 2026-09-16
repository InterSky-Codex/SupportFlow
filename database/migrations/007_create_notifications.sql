CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    message VARCHAR(255) NOT NULL,
    ticket_id BIGINT UNSIGNED NULL,
    actor_id BIGINT UNSIGNED NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY notifications_user_read_created_idx (user_id, is_read, created_at),
    KEY notifications_user_created_idx (user_id, created_at),
    KEY notifications_ticket_created_idx (ticket_id, created_at),

    CONSTRAINT notifications_user_foreign
        FOREIGN KEY (user_id)
        REFERENCES users (id)
        ON DELETE CASCADE,

    CONSTRAINT notifications_ticket_foreign
        FOREIGN KEY (ticket_id)
        REFERENCES tickets (id)
        ON DELETE CASCADE,

    CONSTRAINT notifications_actor_foreign
        FOREIGN KEY (actor_id)
        REFERENCES users (id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
