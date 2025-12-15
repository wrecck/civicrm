<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * CSV Processor for FCS Authorization Upload
 */
class CRM_UnitLedger_BAO_CsvProcessor {

  /**
   * Process uploaded CSV file
   *
   * @param string $filePath Path to uploaded CSV file
   * @return array Results with success count, errors, etc.
   */
  public static function processCsv($filePath) {
    $results = [
      'success' => 0,
      'errors' => [],
      'skipped' => 0,
      'updated' => 0,
      'created' => 0,
    ];

    if (!file_exists($filePath)) {
      $results['errors'][] = 'CSV file not found';
      return $results;
    }

    // Open and parse CSV file
    $handle = fopen($filePath, 'r');
    if ($handle === FALSE) {
      $results['errors'][] = 'Could not open CSV file';
      return $results;
    }

    // Read header row
    $headers = fgetcsv($handle);
    if ($headers === FALSE) {
      $results['errors'][] = 'Could not read CSV headers';
      fclose($handle);
      return $results;
    }

    // Normalize headers (trim whitespace, handle BOM, remove trailing spaces)
    $headers = array_map(function($h) {
      return trim($h, " \t\n\r\0\x0B\xEF\xBB\xBF");
    }, $headers);
    
    // Also normalize row values (trim whitespace)
    $normalizeRow = function($row) {
      return array_map('trim', $row);
    };

    // Map CSV columns to field names
    $columnMap = self::getColumnMap();

    // Process each row
    $rowNum = 1;
    while (($row = fgetcsv($handle)) !== FALSE) {
      $rowNum++;
      
      // Skip empty rows
      if (empty(array_filter($row))) {
        continue;
      }

      // Normalize row values (trim whitespace)
      $row = array_map('trim', $row);

      // Map row data to associative array
      $rowData = [];
      foreach ($headers as $index => $header) {
        $rowData[$header] = isset($row[$index]) ? $row[$index] : '';
      }

      try {
        $result = self::processRow($rowData, $rowNum);
        if ($result['success']) {
          $results['success']++;
          if ($result['created']) {
            $results['created']++;
          } else {
            $results['updated']++;
          }
        } else {
          $results['skipped']++;
          if (!empty($result['error'])) {
            $results['errors'][] = "Row $rowNum: " . $result['error'];
          }
        }
      } catch (Exception $e) {
        $results['skipped']++;
        $results['errors'][] = "Row $rowNum: " . $e->getMessage();
      }
    }

    fclose($handle);
    return $results;
  }

  /**
   * Process a single CSV row
   *
   * @param array $rowData Associative array of row data
   * @param int $rowNum Row number for error reporting
   * @return array Result with success status
   */
  private static function processRow($rowData, $rowNum) {
    $result = ['success' => false, 'created' => false, 'error' => ''];

    // Get required fields
    $assessmentId = trim($rowData['Assessment ID'] ?? '');
    $firstName = trim($rowData['Client First Name'] ?? '');
    $lastName = trim($rowData['Client Last Name'] ?? '');
    $dob = trim($rowData['DOB'] ?? '');
    $serviceType = trim($rowData['Service Type'] ?? '');

    if (empty($assessmentId)) {
      $result['error'] = 'Assessment ID is required';
      return $result;
    }

    if (empty($firstName)) {
      $result['error'] = 'Client First Name is required';
      return $result;
    }

    // Last name can be empty, use "Unknown" as fallback
    if (empty($lastName)) {
      $lastName = 'Unknown';
    }

    // Find or create contact
    $contactId = self::findOrCreateContact($firstName, $lastName, $dob, $rowData);
    if (!$contactId) {
      $result['error'] = 'Could not find or create contact';
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Row ' . $rowNum . ' - Failed to find/create contact for: ' . $firstName . ' ' . $lastName);
      return $result;
    }

    CRM_Core_Error::debug_log_message('UnitLedger CSV: Row ' . $rowNum . ' - Contact ID: ' . $contactId . ', Assessment ID: ' . $assessmentId . ', Service Type: ' . $serviceType);

    // Find or create case (determine case type from Service Type)
    $caseResult = self::findOrCreateCase($contactId, $assessmentId, $rowData, $serviceType);
    if (!$caseResult['success']) {
      $result['error'] = $caseResult['error'];
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Row ' . $rowNum . ' - Failed to find/create case: ' . $caseResult['error']);
      return $result;
    }

    $caseId = $caseResult['case_id'];
    $isNew = $caseResult['created'];

    CRM_Core_Error::debug_log_message('UnitLedger CSV: Row ' . $rowNum . ' - Case ID: ' . $caseId . ' (' . ($isNew ? 'created' : 'updated') . ')');

    // Update case with CSV data
    try {
      self::updateCaseFields($caseId, $rowData);
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Row ' . $rowNum . ' - Successfully updated case fields');
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Row ' . $rowNum . ' - Error updating case fields: ' . $e->getMessage());
      // Don't fail the whole row if field update fails
    }

    // Create FCS Authorization activity
    try {
      $activityId = self::createAuthorizationActivity($contactId, $caseId, $rowData, $serviceType);
      if ($activityId) {
        CRM_Core_Error::debug_log_message('UnitLedger CSV: Row ' . $rowNum . ' - Created activity ID: ' . $activityId);
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Row ' . $rowNum . ' - Error creating activity: ' . $e->getMessage());
      // Don't fail the whole row if activity creation fails
    }

    $result['success'] = true;
    $result['created'] = $isNew;
    return $result;
  }

  /**
   * Find or create a contact
   *
   * @param string $firstName
   * @param string $lastName
   * @param string $dob
   * @param array $rowData Full row data for additional contact fields
   * @return int|null Contact ID
   */
  private static function findOrCreateContact($firstName, $lastName, $dob, $rowData) {
    // Try to find existing contact by name and DOB
    $params = [
      'first_name' => $firstName,
      'last_name' => $lastName,
      'contact_type' => 'Individual',
      'return' => ['id'],
    ];

    if (!empty($dob)) {
      $params['birth_date'] = self::parseDate($dob);
    }

    try {
      $result = civicrm_api3('Contact', 'get', $params);
      if ($result['count'] > 0) {
        return $result['id'];
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding contact: ' . $e->getMessage());
    }

    // Create new contact
    $createParams = [
      'contact_type' => 'Individual',
      'first_name' => $firstName,
      'last_name' => $lastName,
    ];

    if (!empty($dob)) {
      $createParams['birth_date'] = self::parseDate($dob);
    }

    // Add address if provided
    $address = $rowData['Client Mailing Address'] ?? '';
    $city = $rowData['City'] ?? '';
    $state = $rowData['State'] ?? '';
    if (!empty($address) || !empty($city) || !empty($state)) {
      $createParams['api.Address.create'] = [
        'location_type_id' => 'Home',
        'street_address' => $address,
        'city' => $city,
        'state_province_id' => self::getStateProvinceId($state),
      ];
    }

    // Add phone if provided
    $phone = $rowData['Client Contact Number'] ?? '';
    if (!empty($phone)) {
      $createParams['api.Phone.create'] = [
        'phone' => $phone,
        'location_type_id' => 'Home',
      ];
    }

    try {
      $result = civicrm_api3('Contact', 'create', $createParams);
      return $result['id'];
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error creating contact: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Find or create a case
   *
   * @param int $contactId
   * @param string $assessmentId
   * @param array $rowData Full row data
   * @param string $serviceType Service Type from CSV
   * @return array Result with case_id and created flag
   */
  private static function findOrCreateCase($contactId, $assessmentId, $rowData, $serviceType = '') {
    // Determine case type based on Service Type
    $caseTypeName = 'FCS Housing'; // Default
    $fieldPrefix = 'Housing';
    if (stripos($serviceType, 'Employment') !== false) {
      $caseTypeName = 'FCS Employment';
      $fieldPrefix = 'Employment';
    } elseif (stripos($serviceType, 'Housing') !== false || stripos($serviceType, 'Supportive') !== false) {
      $caseTypeName = 'FCS Housing';
      $fieldPrefix = 'Housing';
    }
    
    // Get case type ID first - required for both lookup and creation
    $caseTypeId = self::getCaseTypeId($caseTypeName);
    if (!$caseTypeId) {
      return ['success' => false, 'error' => $caseTypeName . ' case type not found'];
    }
    
    // Try to find existing case by Assessment ID using direct SQL query
    $customFieldName = self::getCustomFieldName($fieldPrefix . ' Assessment ID');
    if ($customFieldName) {
      try {
        $fieldId = str_replace('custom_', '', $customFieldName);
        
        // Validate fieldId is numeric
        if (!is_numeric($fieldId)) {
          CRM_Core_Error::debug_log_message('UnitLedger CSV: Invalid custom field ID: ' . $fieldId);
        } else {
          // Query using custom field table
          $sql = "
            SELECT c.id 
            FROM civicrm_case c
            INNER JOIN civicrm_case_contact cc ON c.id = cc.case_id
            WHERE cc.contact_id = %1
              AND c.case_type_id = %2
              AND c.is_deleted = 0
          ";
          
          $params = [
            1 => [$contactId, 'Integer'],
            2 => [$caseTypeId, 'Integer'],
          ];
        
          // Try to find custom field table name
          $customGroupId = CRM_Core_DAO::singleValueQuery("
            SELECT custom_group_id 
            FROM civicrm_custom_field 
            WHERE id = %1
          ", [1 => [$fieldId, 'Integer']]);
          
          if ($customGroupId) {
            $customGroupTable = CRM_Core_DAO::singleValueQuery("
              SELECT table_name 
              FROM civicrm_custom_group 
              WHERE id = %1
            ", [1 => [$customGroupId, 'Integer']]);
            
            if ($customGroupTable) {
              $columnName = CRM_Core_DAO::singleValueQuery("
                SELECT column_name 
                FROM civicrm_custom_field 
                WHERE id = %1
              ", [1 => [$fieldId, 'Integer']]);
              
              if ($columnName) {
                $sql .= " AND EXISTS (
                  SELECT 1 FROM {$customGroupTable} 
                  WHERE entity_id = c.id 
                  AND {$columnName} = %3
                )";
                $params[3] = [$assessmentId, 'String'];
              }
            }
          }
          
          $sql .= " LIMIT 1";
          
          $caseId = CRM_Core_DAO::singleValueQuery($sql, $params);
          if ($caseId) {
            return ['success' => true, 'case_id' => $caseId, 'created' => false];
          }
        }
      } catch (Exception $e) {
        CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding case: ' . $e->getMessage());
      }
    }
    
    // If we couldn't find by Assessment ID, try simpler lookup by contact and case type
    try {
      $sql = "
        SELECT c.id 
        FROM civicrm_case c
        INNER JOIN civicrm_case_contact cc ON c.id = cc.case_id
        WHERE cc.contact_id = %1
          AND c.case_type_id = %2
          AND c.is_deleted = 0
        ORDER BY c.created_date DESC
        LIMIT 1
      ";
      
      $params = [
        1 => [$contactId, 'Integer'],
        2 => [$caseTypeId, 'Integer'],
      ];
      
      $caseId = CRM_Core_DAO::singleValueQuery($sql, $params);
      if ($caseId) {
        // Found existing case, update it
        return ['success' => true, 'case_id' => $caseId, 'created' => false];
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding case by contact: ' . $e->getMessage());
    }

    // Create new case
    $statusId = self::getCaseStatusId('Open');
    if (!$statusId) {
      // Try to get first available status
      $statusId = CRM_Core_DAO::singleValueQuery("
        SELECT value FROM civicrm_option_value 
        WHERE option_group_id = (SELECT id FROM civicrm_option_group WHERE name = 'case_status')
        AND is_active = 1
        ORDER BY weight ASC
        LIMIT 1
      ");
    }

    $createParams = [
      'case_type_id' => $caseTypeId,
      'contact_id' => $contactId,
      'subject' => $caseTypeName . ' Case - ' . $assessmentId,
    ];
    
    if ($statusId) {
      $createParams['status_id'] = $statusId;
    }

    // Set Assessment ID custom field using API format
    if ($customFieldName) {
      $createParams[$customFieldName] = $assessmentId;
    }

    try {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Creating case with params: ' . json_encode($createParams));
      $result = civicrm_api3('Case', 'create', $createParams);
      
      // API3 returns id directly or in values array
      $caseId = isset($result['id']) ? $result['id'] : (isset($result['values'][$result['id']]['id']) ? $result['values'][$result['id']]['id'] : NULL);
      
      if (!$caseId) {
        // Try to get from values array
        if (isset($result['values']) && is_array($result['values'])) {
          $caseId = reset($result['values'])['id'] ?? NULL;
        }
      }
      
      if (!$caseId) {
        throw new Exception('Case created but ID not returned');
      }
      
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Case created with ID: ' . $caseId);
      
      // If custom field wasn't set via API, set it directly
      if ($customFieldName) {
        $fieldValue = isset($result[$customFieldName]) ? $result[$customFieldName] : NULL;
        if (empty($fieldValue)) {
          self::setCaseCustomField($caseId, $customFieldName, $assessmentId);
        }
      }
      
      return ['success' => true, 'case_id' => $caseId, 'created' => true];
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error creating case: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
      return ['success' => false, 'error' => 'Could not create case: ' . $e->getMessage()];
    }
  }

  /**
   * Update case with CSV data
   *
   * @param int $caseId
   * @param array $rowData CSV row data
   */
  private static function updateCaseFields($caseId, $rowData) {
    // Determine case type to use correct field labels
    $serviceType = trim($rowData['Service Type'] ?? '');
    $prefix = 'Housing'; // Default
    if (stripos($serviceType, 'Employment') !== false) {
      $prefix = 'Employment';
    }
    
    // Map CSV columns to case custom fields (with appropriate prefix)
    $fieldMappings = [
      'Assessment ID' => $prefix . ' Assessment ID',
      'Reauth (R1, R2)' => $prefix . ' Reauth',
      'Medicaid Eligibility Determination' => 'Medicaid Eligibility Determination',
      'Health Needs-Based Criteria' => $prefix . ' Health Needs-Based Criteria',
      'Risk Factors' => $prefix . ' Risk Factors',
      'Enrollment Status' => $prefix . ' Enrollment Status',
      'Assigned Provider Name' => $prefix . ' Assigned Provider (PHI)',
      'Notes' => $prefix . ' Authorization Notes',
      'Auth Start Date' => $prefix . ' Auth Start Date',
      'Auth End Date' => $prefix . ' Auth End Date',
    ];

    // Try API first, then fall back to direct SQL
    $updateParams = ['id' => $caseId];
    $directFields = [];

    foreach ($fieldMappings as $csvColumn => $fieldLabel) {
      $value = trim($rowData[$csvColumn] ?? '');
      if ($value !== '') {
        $customFieldName = self::getCustomFieldName($fieldLabel);
        if ($customFieldName) {
          // Handle date fields specially
          if (strpos($fieldLabel, 'Date') !== false) {
            $value = self::parseDate($value);
          } else {
            // Convert value based on field type (contact reference, option value, etc.)
            $value = self::convertFieldValue($customFieldName, $value, 'Case');
            if ($value === NULL) {
              // Conversion failed, skip this field
              continue;
            }
          }
          
          // Try API format first
          $updateParams[$customFieldName] = $value;
          $directFields[$customFieldName] = $value;
        }
      }
    }

    // Update case via API
    try {
      civicrm_api3('Case', 'create', $updateParams);
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: API update failed, trying direct SQL: ' . $e->getMessage());
      
      // Fall back to direct SQL updates
      foreach ($directFields as $customFieldName => $value) {
        self::setCaseCustomField($caseId, $customFieldName, $value);
      }
    }
  }

  /**
   * Create FCS Authorization activity with all fields populated
   *
   * @param int $contactId
   * @param int $caseId
   * @param array $rowData CSV row data
   * @param string $serviceType Service Type from CSV
   * @return int|null Activity ID
   */
  private static function createAuthorizationActivity($contactId, $caseId, $rowData, $serviceType) {
    // Determine activity type based on Service Type
    $activityTypeName = 'FCS Housing Authorization (Allocation)'; // Default
    $fieldPrefix = 'Housing';
    if (stripos($serviceType, 'Employment') !== false) {
      $activityTypeName = 'FCS Employment Authorization (Allocation)';
      $fieldPrefix = 'Employment';
    } elseif (stripos($serviceType, 'Housing') !== false || stripos($serviceType, 'Supportive') !== false) {
      $activityTypeName = 'FCS Housing Authorization (Allocation)';
      $fieldPrefix = 'Housing';
    }

    // Get activity type ID
    $activityTypeId = self::getActivityTypeId($activityTypeName);
    if (!$activityTypeId) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Activity type not found: ' . $activityTypeName);
      return NULL;
    }

    // Get current user as source contact
    $session = CRM_Core_Session::singleton();
    $sourceContactId = $session->get('userID');
    if (!$sourceContactId) {
      $sourceContactId = 1; // Fallback to admin
    }

    // Prepare activity creation parameters
    $authStartDate = self::parseDate($rowData['Auth Start Date'] ?? '');
    $authEndDate = self::parseDate($rowData['Auth End Date'] ?? '');
    
    $createParams = [
      'activity_type_id' => $activityTypeId,
      'source_contact_id' => $sourceContactId,
      'target_contact_id' => $contactId,
      'subject' => $fieldPrefix . ' Authorization - ' . ($rowData['Assessment ID'] ?? ''),
      'status_id' => 'Completed',
      'activity_date_time' => $authStartDate ?: date('Y-m-d H:i:s'),
      'case_id' => $caseId,
    ];

    // Map CSV columns to activity custom fields
    // Note: Field labels may vary - try multiple variations
    $fieldMappings = [
      'Assessment ID' => [
        $fieldPrefix . ' Assessment ID',
        'Assessment ID',
      ],
      'ProviderOne Number' => [
        $fieldPrefix . ' ProviderOne Number',
        'ProviderOne Number',
      ],
      'Reauth (R1, R2)' => [
        $fieldPrefix . ' Reauth',
        $fieldPrefix . ' Reauth?',
        'Reauth',
        'Reauth?',
      ],
      'Service Type' => [
        $fieldPrefix . ' Service Type',
        'Service Type',
      ],
      'Referring Agency Name' => [
        $fieldPrefix . ' Referring Agency Name',
        'Referring Agency Name',
      ],
      'Medicaid Eligibility Determination' => [
        'Medicaid Eligibility Determination',
        $fieldPrefix . ' Medicaid Eligibility Determination',
      ],
      'Health Needs-Based Criteria' => [
        $fieldPrefix . ' Health Needs-Based Criteria',
        'Health Needs-Based Criteria',
      ],
      'Risk Factors' => [
        $fieldPrefix . ' Risk Factors',
        'Risk Factors',
      ],
      'Assigned Provider Name' => [
        $fieldPrefix . ' Assigned Provider Name',
        'Assigned Provider Name',
      ],
      'Enrollment Status' => [
        $fieldPrefix . ' Enrollment Status',
        'Enrollment Status',
      ],
      'Notes' => [
        $fieldPrefix . ' Notes',
        $fieldPrefix . ' Authorization Notes',
        'Notes',
      ],
      'Benefit Limitation (180 Day Period)' => [
        $fieldPrefix . ' Benefit Limitation (180 Day Period)',
        'Benefit Limitation (180 Day Period)',
        'Benefit Limitation',
      ],
      'Auth Start Date' => [
        $fieldPrefix . ' Auth Start Date',
        'Auth Start Date',
      ],
      'Auth End Date' => [
        $fieldPrefix . ' Auth End Date',
        'Auth End Date',
      ],
    ];

    // Add custom fields to activity
    foreach ($fieldMappings as $csvColumn => $fieldLabelVariations) {
      $value = trim($rowData[$csvColumn] ?? '');
      if ($value !== '') {
        $customFieldName = NULL;
        
        // Try each field label variation
        if (is_array($fieldLabelVariations)) {
          foreach ($fieldLabelVariations as $fieldLabel) {
            $customFieldName = self::getActivityCustomFieldName($fieldLabel);
            if ($customFieldName) {
              break; // Found it, stop searching
            }
          }
        } else {
          $customFieldName = self::getActivityCustomFieldName($fieldLabelVariations);
        }
        
        if ($customFieldName) {
          // Handle date fields specially
          if (stripos($csvColumn, 'Date') !== false) {
            $value = self::parseDate($value);
          }
          // Handle numeric fields
          elseif (stripos($csvColumn, 'Benefit Limitation') !== false) {
            $value = (int) $value;
          }
          // Handle Reauth field - convert "No" to empty or appropriate value
          elseif (stripos($csvColumn, 'Reauth') !== false) {
            if (stripos($value, 'No') !== false || empty($value)) {
              // Leave empty or set to appropriate option value
              // The dropdown will handle the mapping
            }
          } else {
            // Convert value based on field type (contact reference, option value, etc.)
            $value = self::convertFieldValue($customFieldName, $value, 'Activity');
            if ($value === NULL) {
              // Conversion failed, skip this field
              continue;
            }
          }
          
          $createParams[$customFieldName] = $value;
        } else {
          // Log when field is not found (for debugging)
          $labelToTry = is_array($fieldLabelVariations) ? $fieldLabelVariations[0] : $fieldLabelVariations;
          CRM_Core_Error::debug_log_message('UnitLedger CSV: Activity custom field not found for: ' . $labelToTry . ' (CSV column: ' . $csvColumn . ')');
        }
      }
    }

    // Create the activity
    try {
      $result = civicrm_api3('Activity', 'create', $createParams);
      $activityId = isset($result['id']) ? $result['id'] : NULL;
      
      if (!$activityId && isset($result['values']) && is_array($result['values'])) {
        $activityId = reset($result['values'])['id'] ?? NULL;
      }
      
      return $activityId;
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error creating activity: ' . $e->getMessage());
      return NULL;
    }
  }

  /**
   * Get activity type ID by name
   *
   * @param string $activityTypeName
   * @return int|null
   */
  private static function getActivityTypeId($activityTypeName) {
    static $cache = [];

    if (isset($cache[$activityTypeName])) {
      return $cache[$activityTypeName];
    }

    try {
      $result = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_type',
        'name' => $activityTypeName,
        'return' => ['value'],
      ]);

      if ($result['count'] > 0) {
        $id = isset($result['id']) ? $result['values'][$result['id']]['value'] : NULL;
        if ($id) {
          $cache[$activityTypeName] = $id;
          return $id;
        }
      }
      
      // Try by label as fallback
      $result = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_type',
        'label' => $activityTypeName,
        'return' => ['value'],
      ]);

      if ($result['count'] > 0) {
        $id = isset($result['id']) ? $result['values'][$result['id']]['value'] : NULL;
        if ($id) {
          $cache[$activityTypeName] = $id;
          return $id;
        }
      }
      
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Activity type not found: ' . $activityTypeName);
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding activity type: ' . $e->getMessage());
    }

    return NULL;
  }

  /**
   * Get activity custom field name by label
   *
   * @param string $label
   * @return string|null
   */
  private static function getActivityCustomFieldName($label) {
    static $fieldCache = [];

    if (isset($fieldCache[$label])) {
      return $fieldCache[$label];
    }

    try {
      $sql = "
        SELECT cf.id, cf.column_name 
        FROM civicrm_custom_field cf
        JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
        WHERE cf.label = %1 
          AND cg.is_active = 1
          AND cg.extends = 'Activity'
        LIMIT 1
      ";

      $params = [1 => [$label, 'String']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);

      if ($dao->fetch()) {
        $fieldName = 'custom_' . $dao->id;
        $fieldCache[$label] = $fieldName;
        return $fieldName;
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding activity custom field: ' . $e->getMessage());
    }

    return NULL;
  }

  /**
   * Get custom field name by label
   *
   * @param string $label
   * @return string|null
   */
  private static function getCustomFieldName($label) {
    static $fieldCache = [];

    if (isset($fieldCache[$label])) {
      return $fieldCache[$label];
    }

    try {
      $sql = "
        SELECT cf.id, cf.column_name 
        FROM civicrm_custom_field cf
        JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
        WHERE cf.label = %1 AND cg.is_active = 1
        LIMIT 1
      ";

      $params = [1 => [$label, 'String']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);

      if ($dao->fetch()) {
        $fieldName = 'custom_' . $dao->id;
        $fieldCache[$label] = $fieldName;
        return $fieldName;
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding custom field: ' . $e->getMessage());
    }

    return NULL;
  }

  /**
   * Get case type ID by name
   *
   * @param string $caseTypeName
   * @return int|null
   */
  private static function getCaseTypeId($caseTypeName) {
    static $cache = [];

    if (isset($cache[$caseTypeName])) {
      return $cache[$caseTypeName];
    }

    try {
      // Try by name first
      $result = civicrm_api3('CaseType', 'get', [
        'name' => $caseTypeName,
        'return' => ['id'],
      ]);

      if ($result['count'] > 0) {
        // API3 returns id in the first value
        $id = isset($result['id']) ? $result['id'] : (isset($result['values'][$result['id']]['id']) ? $result['values'][$result['id']]['id'] : NULL);
        if ($id) {
          $cache[$caseTypeName] = $id;
          return $id;
        }
      }
      
      // Try by title as fallback
      $result = civicrm_api3('CaseType', 'get', [
        'title' => $caseTypeName,
        'return' => ['id'],
      ]);

      if ($result['count'] > 0) {
        $id = isset($result['id']) ? $result['id'] : (isset($result['values'][$result['id']]['id']) ? $result['values'][$result['id']]['id'] : NULL);
        if ($id) {
          $cache[$caseTypeName] = $id;
          return $id;
        }
      }
      
      // Try direct SQL query as last resort
      $id = CRM_Core_DAO::singleValueQuery("
        SELECT id FROM civicrm_case_type 
        WHERE name = %1 OR title = %1
        LIMIT 1
      ", [1 => [$caseTypeName, 'String']]);
      
      if ($id) {
        $cache[$caseTypeName] = $id;
        return $id;
      }
      
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Case type not found: ' . $caseTypeName);
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding case type "' . $caseTypeName . '": ' . $e->getMessage());
    }

    return NULL;
  }

  /**
   * Get case status ID by name
   *
   * @param string $statusName
   * @return int|null
   */
  private static function getCaseStatusId($statusName) {
    try {
      $result = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'case_status',
        'name' => $statusName,
        'return' => ['value'],
      ]);

      if ($result['count'] > 0) {
        return $result['values'][$result['id']]['value'];
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding case status: ' . $e->getMessage());
    }

    return NULL;
  }

  /**
   * Get state/province ID by abbreviation or name
   *
   * @param string $state
   * @return int|null
   */
  private static function getStateProvinceId($state) {
    if (empty($state)) {
      return NULL;
    }

    try {
      $result = civicrm_api3('StateProvince', 'get', [
        'abbreviation' => $state,
        'return' => ['id'],
      ]);

      if ($result['count'] > 0) {
        return $result['id'];
      }

      // Try by name
      $result = civicrm_api3('StateProvince', 'get', [
        'name' => $state,
        'return' => ['id'],
      ]);

      if ($result['count'] > 0) {
        return $result['id'];
      }
    } catch (Exception $e) {
      // Ignore errors
    }

    return NULL;
  }

  /**
   * Parse date string to YYYY-MM-DD format
   *
   * @param string $dateString
   * @return string|null
   */
  private static function parseDate($dateString) {
    if (empty($dateString)) {
      return NULL;
    }

    // Try various date formats (including formats without leading zeros)
    $formats = ['Y-m-d', 'm/d/Y', 'n/j/Y', 'm-d-Y', 'n-j-Y', 'Y/m/d', 'd/m/Y', 'd-m-Y'];
    
    foreach ($formats as $format) {
      $date = DateTime::createFromFormat($format, $dateString);
      if ($date !== FALSE) {
        return $date->format('Y-m-d');
      }
    }

    // Try strtotime as fallback
    $timestamp = strtotime($dateString);
    if ($timestamp !== FALSE) {
      return date('Y-m-d', $timestamp);
    }

    return NULL;
  }

  /**
   * Get custom field data type and info
   *
   * @param string $customFieldName e.g., 'custom_123'
   * @param string $entityType 'Case' or 'Activity'
   * @return array|null Field info with data_type, html_type, etc.
   */
  private static function getCustomFieldInfo($customFieldName, $entityType = 'Case') {
    static $fieldInfoCache = [];
    $cacheKey = $customFieldName . '_' . $entityType;
    
    if (isset($fieldInfoCache[$cacheKey])) {
      return $fieldInfoCache[$cacheKey];
    }
    
    try {
      $fieldId = str_replace('custom_', '', $customFieldName);
      
      $sql = "
        SELECT cf.data_type, cf.html_type, cf.column_name, cg.table_name, 
               cf.option_group_id, cf.filter
        FROM civicrm_custom_field cf
        JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
        WHERE cf.id = %1 AND cg.extends = %2
      ";
      
      $dao = CRM_Core_DAO::executeQuery($sql, [
        1 => [$fieldId, 'Integer'],
        2 => [$entityType, 'String'],
      ]);
      
      if ($dao->fetch()) {
        $info = [
          'data_type' => $dao->data_type,
          'html_type' => $dao->html_type,
          'column_name' => $dao->column_name,
          'table_name' => $dao->table_name,
          'option_group_id' => $dao->option_group_id,
          'filter' => $dao->filter,
        ];
        $fieldInfoCache[$cacheKey] = $info;
        return $info;
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error getting custom field info: ' . $e->getMessage());
    }
    
    return NULL;
  }

  /**
   * Convert field value based on field type (contact reference, option value, etc.)
   *
   * @param string $customFieldName
   * @param mixed $value
   * @param string $entityType 'Case' or 'Activity'
   * @return mixed Converted value
   */
  private static function convertFieldValue($customFieldName, $value, $entityType = 'Case') {
    if (empty($value)) {
      return $value;
    }
    
    $fieldInfo = self::getCustomFieldInfo($customFieldName, $entityType);
    if (!$fieldInfo) {
      return $value;
    }
    
    // Handle contact reference fields
    if ($fieldInfo['data_type'] === 'ContactReference' || 
        ($fieldInfo['html_type'] === 'Select' && !empty($fieldInfo['filter']) && strpos($fieldInfo['filter'], 'contact_type') !== false)) {
      // Look up contact by name
      $contactId = self::findContactByName($value);
      if ($contactId) {
        return $contactId;
      }
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Contact not found for: ' . $value);
      return NULL;
    }
    
    // Handle option value fields (Select, Radio, CheckBox, etc.)
    if (in_array($fieldInfo['html_type'], ['Select', 'Radio', 'CheckBox', 'Multi-Select']) && !empty($fieldInfo['option_group_id'])) {
      $optionValue = self::getOptionValueId($fieldInfo['option_group_id'], $value);
      if ($optionValue !== NULL) {
        return $optionValue;
      }
      // If not found, try to use the value as-is (might already be an ID)
      if (is_numeric($value)) {
        return (int) $value;
      }
    }
    
    return $value;
  }

  /**
   * Find contact by name (organization or individual)
   *
   * @param string $name
   * @return int|null Contact ID
   */
  private static function findContactByName($name) {
    if (empty($name)) {
      return NULL;
    }
    
    try {
      // Try organization first (for providers/agencies)
      $result = civicrm_api3('Contact', 'get', [
        'contact_type' => 'Organization',
        'organization_name' => $name,
        'return' => ['id'],
        'options' => ['limit' => 1],
      ]);
      
      if ($result['count'] > 0) {
        return $result['id'];
      }
      
      // Try individual by display name
      $result = civicrm_api3('Contact', 'get', [
        'display_name' => $name,
        'return' => ['id'],
        'options' => ['limit' => 1],
      ]);
      
      if ($result['count'] > 0) {
        return $result['id'];
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding contact: ' . $e->getMessage());
    }
    
    return NULL;
  }

  /**
   * Get option value ID by label
   *
   * @param int $optionGroupId
   * @param string $label
   * @return int|null
   */
  private static function getOptionValueId($optionGroupId, $label) {
    if (empty($label)) {
      return NULL;
    }
    
    try {
      $result = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => $optionGroupId,
        'label' => $label,
        'return' => ['value'],
      ]);
      
      if ($result['count'] > 0) {
        $id = isset($result['id']) ? $result['values'][$result['id']]['value'] : NULL;
        return $id;
      }
    } catch (Exception $e) {
      // Ignore errors
    }
    
    return NULL;
  }

  /**
   * Set a custom field value directly on a case
   *
   * @param int $caseId
   * @param string $customFieldName e.g., 'custom_123'
   * @param mixed $value
   */
  private static function setCaseCustomField($caseId, $customFieldName, $value) {
    try {
      $fieldId = str_replace('custom_', '', $customFieldName);
      
      // Get custom group and field info
      $fieldInfo = self::getCustomFieldInfo($customFieldName, 'Case');
      if (!$fieldInfo) {
        return;
      }
      
      // Convert value based on field type
      $convertedValue = self::convertFieldValue($customFieldName, $value, 'Case');
      if ($convertedValue === NULL && $value !== '') {
        // Conversion failed, skip this field
        return;
      }
      
      $tableName = $fieldInfo['table_name'];
      $columnName = $fieldInfo['column_name'];
      
      // Determine data type for SQL parameter
      $dataType = 'String';
      if ($fieldInfo['data_type'] === 'Int' || $fieldInfo['data_type'] === 'Integer') {
        $dataType = 'Integer';
        $convertedValue = (int) $convertedValue;
      } elseif ($fieldInfo['data_type'] === 'Float' || $fieldInfo['data_type'] === 'Money') {
        $dataType = 'Float';
        $convertedValue = (float) $convertedValue;
      }
      
      // Check if record exists
      $exists = CRM_Core_DAO::singleValueQuery("
        SELECT entity_id FROM {$tableName} WHERE entity_id = %1
      ", [1 => [$caseId, 'Integer']]);
      
      if ($exists) {
        // Update
        CRM_Core_DAO::executeQuery("
          UPDATE {$tableName} 
          SET {$columnName} = %1 
          WHERE entity_id = %2
        ", [
          1 => [$convertedValue, $dataType],
          2 => [$caseId, 'Integer'],
        ]);
      } else {
        // Insert
        CRM_Core_DAO::executeQuery("
          INSERT INTO {$tableName} (entity_id, {$columnName}) 
          VALUES (%1, %2)
        ", [
          1 => [$caseId, 'Integer'],
          2 => [$convertedValue, $dataType],
        ]);
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error setting custom field: ' . $e->getMessage());
    }
  }

  /**
   * Get CSV column mapping
   *
   * @return array
   */
  private static function getColumnMap() {
    return [
      'Assessment ID',
      'Reauth (R1, R2)',
      'Service Type',
      'Referring Agency Name',
      'Client First Name',
      'Client Last Name',
      'DOB',
      'ProviderOne Number',
      'Client Mailing Address',
      'City',
      'State',
      'Client Contact Number',
      'Medicaid Eligibility Determination',
      'Health Needs-Based Criteria',
      'Risk Factors',
      'Assigned Provider Name',
      'Enrollment Status',
      'Notes',
      'Benefit Limitation (180 Day Period)',
      'Auth Start Date',
      'Auth End Date',
    ];
  }
}
