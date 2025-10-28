-- Add FCS Authorization Upload menu item under Cases > Case Reports
-- First, delete any existing entry
DELETE FROM civicrm_navigation WHERE name = 'unit_ledger_csv_upload';

-- Find Case Reports menu ID
SET @case_reports_id = (
  SELECT id FROM civicrm_navigation 
  WHERE name = 'Case Reports' AND parent_id IN (
    SELECT id FROM civicrm_navigation WHERE name = 'Cases'
  ) 
  LIMIT 1
);

-- Insert the CSV Upload menu item under Case Reports
INSERT INTO civicrm_navigation 
(domain_id, label, name, url, permission, permission_operator, parent_id, is_active, has_separator, weight) 
VALUES (1, 'FCS Authorization Upload', 'unit_ledger_csv_upload', 'civicrm/unitledger/csv-upload', 'access CiviCRM', 'AND', @case_reports_id, 1, 0, 1);

-- Clear navigation cache
UPDATE civicrm_setting SET value = NULL WHERE name = 'navigation' AND group_name = 'CiviCRM Preferences';
