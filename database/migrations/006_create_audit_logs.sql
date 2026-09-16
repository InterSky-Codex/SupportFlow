CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    ticket_id BIGINT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    metadata JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY audit_logs_ticket_created_idx (ticket_id, created_at),
    KEY audit_logs_actor_created_idx (actor_id, created_at),
    KEY audit_logs_action_idx (action),
    KEY audit_logs_created_at_idx (created_at),

    CONSTRAINT audit_logs_actor_foreign
        FOREIGN KEY (actor_id)
        REFERENCES users (id)
        ON DELETE SET NULL,

    CONSTRAINT audit_logs_ticket_foreign
        FOREIGN KEY (ticket_id)
        REFERENCES tickets (id)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;
