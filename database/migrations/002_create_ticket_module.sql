CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY categories_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE priorities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color CHAR(7) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY priorities_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE statuses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    color CHAR(7) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY statuses_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(20) NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    priority_id BIGINT UNSIGNED NOT NULL,
    status_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    assigned_to BIGINT UNSIGNED NULL,
    due_date DATE NULL,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY tickets_number_unique (ticket_number),
    KEY tickets_status_priority_index (status_id, priority_id),
    KEY tickets_creator_index (created_by),
    KEY tickets_assignee_index (assigned_to),
    CONSTRAINT tickets_category_foreign FOREIGN KEY (category_id) REFERENCES categories (id),
    CONSTRAINT tickets_priority_foreign FOREIGN KEY (priority_id) REFERENCES priorities (id),
    CONSTRAINT tickets_status_foreign FOREIGN KEY (status_id) REFERENCES statuses (id),
    CONSTRAINT tickets_creator_foreign FOREIGN KEY (created_by) REFERENCES users (id),
    CONSTRAINT tickets_assignee_foreign FOREIGN KEY (assigned_to) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name, sort_order) VALUES
    ('Hardware', 10), ('Software', 20), ('Printer', 30), ('Internet', 40), ('Network', 50),
    ('Email', 60), ('CCTV', 70), ('Security', 80), ('Access', 90), ('Request', 100), ('Other', 110);

INSERT INTO priorities (name, color, sort_order) VALUES
    ('Low', '#64748b', 10), ('Medium', '#2563eb', 20), ('High', '#ea580c', 30), ('Critical', '#dc2626', 40);

INSERT INTO statuses (name, color, sort_order) VALUES
    ('Open', '#2563eb', 10), ('Assigned', '#7c3aed', 20), ('In Progress', '#d97706', 30),
    ('Pending', '#64748b', 40), ('Resolved', '#059669', 50), ('Closed', '#334155', 60);
