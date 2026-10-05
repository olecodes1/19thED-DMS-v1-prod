<?php

function ensure_feature_tables(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS media_items (
            media_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            media_type ENUM('image','video','audio') NOT NULL,
            category VARCHAR(120) NULL,
            tags VARCHAR(255) NULL,
            event_tag VARCHAR(120) NULL,
            person_tag VARCHAR(120) NULL,
            media_year INT NULL,
            description TEXT NULL,
            file_path VARCHAR(255) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS legacy_leaders (
            leader_id INT AUTO_INCREMENT PRIMARY KEY,
            role_type ENUM('Bishop','Director','President','Mother Director','Other') NOT NULL DEFAULT 'Other',
            full_name VARCHAR(255) NOT NULL,
            conference_name VARCHAR(255) NULL,
            start_year INT NULL,
            end_year INT NULL,
            descriptions TEXT NULL,
            achievements TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS milestones (
            milestone_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            milestone_year INT NOT NULL,
            descriptions TEXT NULL,
            achievements TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS story_pages (
            story_id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            story_year INT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'draft',
            cover_media_id INT NULL,
            content LONGTEXT NULL,
            media_ids_json LONGTEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_attendance_breakdowns (
            breakdown_id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            conference_id INT NULL,
            area_id INT NULL,
            church_id INT NULL,
            attendance_count INT NOT NULL DEFAULT 0,
            notes VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS import_jobs (
            job_id INT AUTO_INCREMENT PRIMARY KEY,
            job_type VARCHAR(60) NOT NULL,
            source_file_name VARCHAR(255) NULL,
            conference_id INT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'previewed',
            valid_count INT NOT NULL DEFAULT 0,
            invalid_count INT NOT NULL DEFAULT 0,
            inserted_count INT NOT NULL DEFAULT 0,
            skipped_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS import_review_queue (
            queue_id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            row_number INT NOT NULL,
            issue_reason VARCHAR(255) NOT NULL,
            row_json LONGTEXT NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            resolved_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS deleted_items (
            deleted_id INT AUTO_INCREMENT PRIMARY KEY,
            entity_table VARCHAR(80) NOT NULL,
            entity_id INT NOT NULL,
            source_path VARCHAR(255) NULL,
            data_json LONGTEXT NOT NULL,
            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            restored_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            user_id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('superadmin','conference_admin') NOT NULL DEFAULT 'conference_admin',
            conference_id INT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    add_column_if_missing($pdo, 'events', 'conference_id', 'INT NULL');
    add_column_if_missing($pdo, 'events', 'episcopal_district_id', 'INT NULL DEFAULT 19');
    add_column_if_missing($pdo, 'events', 'attendance_count', 'INT NOT NULL DEFAULT 0');

    // Members: columns present in the CSV but missing from original schema
    add_column_if_missing($pdo, 'members', 'member_no',            "VARCHAR(30) NULL COMMENT 'Member_No. from CSV'");
    add_column_if_missing($pdo, 'members', 'joined_ypd',           "ENUM('Yes','No') NULL COMMENT 'Joined_Member_of_the_YPD'");
    add_column_if_missing($pdo, 'members', 'full_member_of_church', "ENUM('Yes','No') NULL COMMENT 'Full_Member_of_the_Church'");
    add_column_if_missing($pdo, 'members', 'occupational_status',   "VARCHAR(60) NULL COMMENT 'Occupational_Status from CSV'");

    add_column_if_missing($pdo, 'media_items', 'tags', 'VARCHAR(255) NULL');
    add_column_if_missing($pdo, 'media_items', 'event_tag', 'VARCHAR(120) NULL');
    add_column_if_missing($pdo, 'media_items', 'person_tag', 'VARCHAR(120) NULL');
    add_column_if_missing($pdo, 'media_items', 'media_year', 'INT NULL');
    add_column_if_missing($pdo, 'media_items', 'deleted_at', 'DATETIME NULL');
    add_column_if_missing($pdo, 'story_pages', 'status', "VARCHAR(20) NOT NULL DEFAULT 'draft'");
    add_column_if_missing($pdo, 'story_pages', 'cover_media_id', 'INT NULL');
    add_column_if_missing($pdo, 'story_pages', 'deleted_at', 'DATETIME NULL');
    add_column_if_missing($pdo, 'deleted_items', 'source_path', 'VARCHAR(255) NULL');
    add_column_if_missing($pdo, 'users', 'conference_id', 'INT NULL');
    add_column_if_missing($pdo, 'users', 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');
    add_column_if_missing($pdo, 'users', 'updated_at', 'DATETIME NULL');
    add_column_if_missing($pdo, 'users', 'must_change_password', 'TINYINT(1) NOT NULL DEFAULT 0');

    // Seed one superadmin user if none exists.
    $hasSuperadmin = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='superadmin'")->fetchColumn();
    if ($hasSuperadmin === 0) {
        // Use environment variable for initial password, or generate a random one
        $initialPassword = getenv('INITIAL_SUPERADMIN_PASSWORD') ?: bin2hex(random_bytes(16));
        $seed = $pdo->prepare("INSERT INTO users (username, password_hash, role, conference_id, is_active, must_change_password) VALUES (?, ?, 'superadmin', NULL, 1, ?)");
        $seed->execute([
            'superadmin',
            password_hash($initialPassword, PASSWORD_DEFAULT),
            1 // must_change_password — force a new password on first login (§1.7)
        ]);
        // Log the initial password for first-time setup (should be changed immediately)
        error_log("Initial superadmin password created: " . $initialPassword);
    }
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function add_column_if_missing(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    }
}
