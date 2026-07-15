-- Finance & Accounting Module Schema

CREATE TABLE `chart_of_accounts` (
    `id` int NOT NULL AUTO_INCREMENT,
    `account_number` varchar(20) NOT NULL,
    `name` varchar(100) NOT NULL,
    `type` enum('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    `normal_balance` enum('debit', 'credit') NOT NULL,
    `parent_id` int DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_chart_of_accounts_number` (`account_number`),
    KEY `idx_chart_of_accounts_type` (`type`),
    KEY `idx_chart_of_accounts_parent` (`parent_id`),
    CONSTRAINT `fk_chart_of_accounts_parent` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `financial_accounts` (
    `id` int NOT NULL AUTO_INCREMENT,
    `account_type` enum('cash', 'bank', 'merchant') NOT NULL,
    `name` varchar(100) NOT NULL,
    `chart_of_account_id` int NOT NULL,
    `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
    `currency` varchar(3) NOT NULL DEFAULT 'PKR',
    `status` enum('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_financial_accounts_type` (`account_type`),
    KEY `idx_financial_accounts_chart_of_account` (`chart_of_account_id`),
    CONSTRAINT `fk_financial_accounts_chart_of_account` FOREIGN KEY (`chart_of_account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `general_ledger` (
    `account_id` int NOT NULL,
    `balance` decimal(15,2) NOT NULL DEFAULT '0.00',
    `last_updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`account_id`),
    CONSTRAINT `fk_general_ledger_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `journal_entries` (
    `id` bigint NOT NULL AUTO_INCREMENT,
    `transaction_id` char(36) NOT NULL,
    `description` varchar(255) NOT NULL,
    `transaction_date` date NOT NULL,
    `source_id` bigint DEFAULT NULL,
    `source_type` varchar(100) DEFAULT NULL,
    `created_by` int NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_journal_entries_transaction_id` (`transaction_id`),
    KEY `idx_journal_entries_date` (`transaction_date`),
    KEY `idx_journal_entries_source` (`source_type`, `source_id`),
    CONSTRAINT `fk_journal_entries_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `journal_entry_lines` (
    `id` bigint NOT NULL AUTO_INCREMENT,
    `journal_entry_id` bigint NOT NULL,
    `account_id` int NOT NULL,
    `debit_amount` decimal(15,2) DEFAULT NULL,
    `credit_amount` decimal(15,2) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_journal_entry_lines_entry_id` (`journal_entry_id`),
    KEY `idx_journal_entry_lines_account_id` (`account_id`),
    CONSTRAINT `fk_journal_entry_lines_entry` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_journal_entry_lines_account` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `expenses` (
    `id` bigint NOT NULL AUTO_INCREMENT,
    `category` varchar(100) NOT NULL,
    `amount` decimal(15,2) NOT NULL,
    `transaction_date` date NOT NULL,
    `description` text,
    `financial_account_id` int NOT NULL,
    `chart_of_account_id` int NOT NULL,
    `created_by` int NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_expenses_category` (`category`),
    KEY `idx_expenses_date` (`transaction_date`),
    CONSTRAINT `fk_expenses_financial_account` FOREIGN KEY (`financial_account_id`) REFERENCES `financial_accounts` (`id`),
    CONSTRAINT `fk_expenses_chart_of_account` FOREIGN KEY (`chart_of_account_id`) REFERENCES `chart_of_accounts` (`id`),
    CONSTRAINT `fk_expenses_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `revenue` (
    `id` bigint NOT NULL AUTO_INCREMENT,
    `category` varchar(100) NOT NULL,
    `amount` decimal(15,2) NOT NULL,
    `transaction_date` date NOT NULL,
    `description` text,
    `financial_account_id` int NOT NULL,
    `chart_of_account_id` int NOT NULL,
    `created_by` int NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_revenue_category` (`category`),
    KEY `idx_revenue_date` (`transaction_date`),
    CONSTRAINT `fk_revenue_financial_account` FOREIGN KEY (`financial_account_id`) REFERENCES `financial_accounts` (`id`),
    CONSTRAINT `fk_revenue_chart_of_account` FOREIGN KEY (`chart_of_account_id`) REFERENCES `chart_of_accounts` (`id`),
    CONSTRAINT `fk_revenue_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Permissions
INSERT INTO permissions (name, description) VALUES
('finance.view', 'View dashboard, ledger, accounts, transactions'),
('finance.create', 'Create manual journal entries'),
('finance.update', 'Update properties or reverse entries'),
('finance.delete', 'Soft delete non-system entries'),
('finance.manage', 'Full control over Chart of Accounts'),
('finance.reports', 'View and export financial reports'),
('expenses.manage', 'Manage expense operations'),
('revenue.manage', 'Manage revenue operations'),
('accounts.manage', 'Setup and manage financial bank/cash accounts')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Assign permissions to admin role (assuming role_id = 1 is superadmin/admin)
INSERT INTO permission_role (permission_id, role_id)
SELECT id, 1 FROM permissions WHERE name IN (
    'finance.view', 'finance.create', 'finance.update', 'finance.delete',
    'finance.manage', 'finance.reports', 'expenses.manage', 'revenue.manage', 'accounts.manage'
) ON DUPLICATE KEY UPDATE role_id = VALUES(role_id);

