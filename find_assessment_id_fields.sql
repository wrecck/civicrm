-- Find Assessment ID custom fields for Cases
-- Run this query in phpMyAdmin or your MySQL client to find the custom field IDs

-- For Housing Assessment ID (Case)
SELECT 
    cf.id AS custom_field_id,
    'custom_' || cf.id AS api_field_name,
    cf.label,
    cf.column_name,
    cg.name AS custom_group_name,
    cg.table_name,
    cg.extends AS entity_type
FROM civicrm_custom_field cf
JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
WHERE cf.label = 'Housing Assessment ID'
  AND cg.extends = 'Case'
  AND cg.is_active = 1
  AND cf.is_active = 1;

-- For Employment Assessment ID (Case)
SELECT 
    cf.id AS custom_field_id,
    'custom_' || cf.id AS api_field_name,
    cf.label,
    cf.column_name,
    cg.name AS custom_group_name,
    cg.table_name,
    cg.extends AS entity_type
FROM civicrm_custom_field cf
JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
WHERE cf.label = 'Employment Assessment ID'
  AND cg.extends = 'Case'
  AND cg.is_active = 1
  AND cf.is_active = 1;

-- Alternative: Find all Assessment ID fields (to see what exists)
SELECT 
    cf.id AS custom_field_id,
    CONCAT('custom_', cf.id) AS api_field_name,
    cf.label,
    cf.column_name,
    cg.name AS custom_group_name,
    cg.table_name,
    cg.extends AS entity_type
FROM civicrm_custom_field cf
JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
WHERE cf.label LIKE '%Assessment ID%'
  AND cg.is_active = 1
  AND cf.is_active = 1
ORDER BY cg.extends, cf.label;
