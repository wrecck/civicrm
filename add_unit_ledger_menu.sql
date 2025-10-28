-- Add Unit Ledger menu item under Reports
-- First, delete any existing entry
DELETE FROM civicrm_navigation WHERE name = 'unit_ledger';

-- Insert the Unit Ledger menu item under Reports (ID 230)
INSERT INTO civicrm_navigation 
(domain_id, label, name, url, permission, permission_operator, parent_id, is_active, has_separator, weight) 
VALUES (1, 'Unit Ledger', 'unit_ledger', 'civicrm/unitledger', 'access CiviCRM', 'AND', 230, 1, 0, 1);

-- Clear navigation cache
UPDATE civicrm_setting SET value = NULL WHERE name = 'navigation' AND group_name = 'CiviCRM Preferences';
