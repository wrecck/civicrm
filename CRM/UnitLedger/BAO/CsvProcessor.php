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
      return $result;
    }

    // Find or create case (determine case type from Service Type)
    $caseResult = self::findOrCreateCase($contactId, $assessmentId, $rowData, $serviceType);
    if (!$caseResult['success']) {
      $result['error'] = $caseResult['error'];
      return $result;
    }

    $caseId = $caseResult['case_id'];
    $isNew = $caseResult['created'];

    // Update case with CSV data
    self::updateCaseFields($caseId, $rowData);

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
    
    // Try to find existing case by Assessment ID using direct SQL query
    $customFieldName = self::getCustomFieldName($fieldPrefix . ' Assessment ID');
    if ($customFieldName) {
      try {
        $fieldId = str_replace('custom_', '', $customFieldName);
        $caseTypeId = self::getCaseTypeId($caseTypeName);
        
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
      } catch (Exception $e) {
        CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding case: ' . $e->getMessage());
      }
    }

    // Create new case
    $caseTypeId = self::getCaseTypeId($caseTypeName);
    if (!$caseTypeId) {
      return ['success' => false, 'error' => $caseTypeName . ' case type not found'];
    }

    $createParams = [
      'case_type_id' => $caseTypeId,
      'contact_id' => $contactId,
      'subject' => $caseTypeName . ' Case - ' . $assessmentId,
      'status_id' => self::getCaseStatusId('Open'),
    ];

    // Set Assessment ID custom field using API format
    if ($customFieldName) {
      $createParams[$customFieldName] = $assessmentId;
    }

    try {
      $result = civicrm_api3('Case', 'create', $createParams);
      $caseId = $result['id'];
      
      // If custom field wasn't set via API, set it directly
      if ($customFieldName && empty($result[$customFieldName])) {
        self::setCaseCustomField($caseId, $customFieldName, $assessmentId);
      }
      
      return ['success' => true, 'case_id' => $caseId, 'created' => true];
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error creating case: ' . $e->getMessage());
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
      $value = $rowData[$csvColumn] ?? '';
      if ($value !== '') {
        $customFieldName = self::getCustomFieldName($fieldLabel);
        if ($customFieldName) {
          // Handle date fields specially
          if (strpos($fieldLabel, 'Date') !== false) {
            $value = self::parseDate($value);
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
      $result = civicrm_api3('CaseType', 'get', [
        'name' => $caseTypeName,
        'return' => ['id'],
      ]);

      if ($result['count'] > 0) {
        $id = $result['id'];
        $cache[$caseTypeName] = $id;
        return $id;
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('UnitLedger CSV: Error finding case type: ' . $e->getMessage());
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
      $sql = "
        SELECT cg.table_name, cf.column_name
        FROM civicrm_custom_field cf
        JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
        WHERE cf.id = %1 AND cg.extends = 'Case'
      ";
      
      $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$fieldId, 'Integer']]);
      if ($dao->fetch()) {
        $tableName = $dao->table_name;
        $columnName = $dao->column_name;
        
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
            1 => [$value, 'String'],
            2 => [$caseId, 'Integer'],
          ]);
        } else {
          // Insert
          CRM_Core_DAO::executeQuery("
            INSERT INTO {$tableName} (entity_id, {$columnName}) 
            VALUES (%1, %2)
          ", [
            1 => [$caseId, 'Integer'],
            2 => [$value, 'String'],
          ]);
        }
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
