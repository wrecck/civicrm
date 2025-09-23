-- Unit Ledger Extension Database Schema
-- This table stores append-only unit ledger entries linked to activities

CREATE TABLE IF NOT EXISTS `civicrm_unit_ledger` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique UnitLedger ID',
  `activity_id` int unsigned DEFAULT NULL COMMENT 'FK to Activity',
  `case_id` int unsigned DEFAULT NULL COMMENT 'FK to Case',
  `contact_id` int unsigned DEFAULT NULL COMMENT 'FK to Contact',
  `program` varchar(255) DEFAULT NULL COMMENT 'Program name (e.g., FCS, WellPoint)',
  `units` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Number of units (positive for credits, negative for debits)',
  `unit_type` varchar(50) DEFAULT 'hours' COMMENT 'Type of units (hours, days, sessions, etc.)',
  `transaction_date` datetime NOT NULL COMMENT 'Date/time of the transaction',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Running balance after this transaction',
  `last_posted_units` decimal(10,2) DEFAULT NULL COMMENT 'Last units posted for this activity (for idempotency)',
  `transaction_type` varchar(50) DEFAULT 'activity' COMMENT 'Type of transaction (activity, adjustment, import)',
  `description` text DEFAULT NULL COMMENT 'Description of the transaction',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this record was created',
  `created_by` int unsigned DEFAULT NULL COMMENT 'FK to Contact who created this record',
  PRIMARY KEY (`id`),
  KEY `idx_activity` (`activity_id`),
  KEY `idx_case` (`case_id`),
  KEY `idx_contact` (`contact_id`),
  KEY `idx_program_date` (`program`, `transaction_date`),
  KEY `idx_transaction_date` (`transaction_date`),
  KEY `idx_balance` (`balance`),
  CONSTRAINT `FK_civicrm_unit_ledger_activity_id` FOREIGN KEY (`activity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_civicrm_unit_ledger_case_id` FOREIGN KEY (`case_id`) REFERENCES `civicrm_case` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_civicrm_unit_ledger_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL,
  CONSTRAINT `FK_civicrm_unit_ledger_created_by` FOREIGN KEY (`created_by`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Append-only unit ledger for tracking unit transactions';

-- Add custom field to activities to track last posted units for idempotency
INSERT IGNORE INTO `civicrm_custom_group` (
  `name`, `title`, `extends`, `extends_entity_column_value`, `extends_entity_column_id`, `style`, `collapse_display`, `help_pre`, `help_post`, `weight`, `is_active`, `table_name`, `is_multiple`, `min_multiple`, `max_multiple`, `collapse_adv_display`, `created_date`, `is_reserved`, `is_public`
) VALUES (
  'unit_ledger_tracking', 'Unit Ledger Tracking', 'Activity', NULL, NULL, 'Inline', 1, 'Internal tracking fields for unit ledger integration', '', 1, 1, 'civicrm_value_unit_ledger_tracking', 0, NULL, NULL, 0, NOW(), 0, 0
);

SET @custom_group_id = LAST_INSERT_ID();

INSERT IGNORE INTO `civicrm_custom_field` (
  `custom_group_id`, `name`, `label`, `data_type`, `html_type`, `default_value`, `is_required`, `is_searchable`, `is_search_range`, `weight`, `help_pre`, `help_post`, `mask`, `attributes`, `javascript`, `is_active`, `is_view`, `options_per_line`, `text_length`, `start_date_years`, `end_date_years`, `date_format`, `time_format`, `note_columns`, `note_rows`, `column_name`, `option_group_id`, `filter`, `in_selector`
) VALUES (
  @custom_group_id, 'last_posted_units', 'Last Posted Units', 'Float', 'Text', NULL, 0, 0, 0, 1, 'Internal field tracking last units posted for this activity', '', NULL, NULL, NULL, 1, 0, 1, 255, NULL, NULL, NULL, NULL, 60, 4, 'last_posted_units', NULL, NULL, 0
);

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS `idx_unit_ledger_activity_program` ON `civicrm_unit_ledger` (`activity_id`, `program`);
CREATE INDEX IF NOT EXISTS `idx_unit_ledger_case_program_date` ON `civicrm_unit_ledger` (`case_id`, `program`, `transaction_date`);
CREATE INDEX IF NOT EXISTS `idx_unit_ledger_contact_program_date` ON `civicrm_unit_ledger` (`contact_id`, `program`, `transaction_date`);
