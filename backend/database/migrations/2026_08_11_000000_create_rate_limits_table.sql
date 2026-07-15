-- Rate limiting table for API abuse prevention.
CREATE TABLE IF NOT EXISTS api_rate_limits (
    identifier VARCHAR(255) NOT NULL,
    window VARCHAR(16) NOT NULL COMMENT 'YYYY-MM-DD HH:MM minute window',
    count INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (identifier, window),
    KEY idx_api_rate_limits_window (window, count)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
