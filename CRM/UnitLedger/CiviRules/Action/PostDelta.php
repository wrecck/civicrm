<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Class for Unit Ledger Post Delta Action
 */
class CRM_UnitLedger_CiviRules_Action_PostDelta extends CRM_Civirules_Action {

  /**
   * Process the action
   *
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   */
  public function processAction(CRM_Civirules_TriggerData_TriggerData $triggerData) {
    try {
      // Debug: Log what we're getting
      $this->logAction('PostDelta triggered', $triggerData, \Psr\Log\LogLevel::INFO);
      
      // Get the activity data - try different ways depending on trigger
      $activity = $triggerData->getEntityData('Activity');
      
      // If no activity data, try to get it from case activity trigger
      if (empty($activity)) {
        $case = $triggerData->getEntityData('Case');
        if (!empty($case['activities'])) {
          // Get the most recent activity from the case
          $activity = end($case['activities']);
        }
      }
      
      if (empty($activity)) {
        $this->logAction('No activity data found in trigger', $triggerData, \Psr\Log\LogLevel::ERROR);
        return;
      }

      // Debug: Log the activity data
      $this->logAction('Activity data: ' . json_encode($activity), $triggerData, \Psr\Log\LogLevel::INFO);

      $activityId = $activity['id'] ?? NULL;
      
      // Check if we have custom field data, if not, fetch the complete activity
      if (empty($activity['custom_']) && !empty($activityId)) {
        try {
          $fullActivity = civicrm_api3('Activity', 'get', [
            'id' => $activityId,
            'return' => ['custom_*', 'duration', 'activity_type_id', 'case_id']
          ]);
          
          if ($fullActivity['count'] > 0) {
            $activity = array_merge($activity, $fullActivity['values'][$activityId]);
            $this->logAction('Fetched full activity data: ' . json_encode($activity), $triggerData, \Psr\Log\LogLevel::INFO);
          }
        } catch (Exception $e) {
          $this->logAction('Error fetching full activity data: ' . $e->getMessage(), $triggerData, \Psr\Log\LogLevel::ERROR);
        }
      }
      $activityType = $activity['activity_type_id'] ?? NULL;
      $caseId = $activity['case_id'] ?? NULL;
      
      if (empty($activityId) || empty($activityType)) {
        $this->logAction('Missing activity ID or type', $triggerData, \Psr\Log\LogLevel::ERROR);
        return;
      }
      
      // Get contact ID properly - handle both numeric and array cases
      $contactId = $triggerData->getContactId();
      if (empty($contactId) || !is_numeric($contactId)) {
        // Try to get contact ID from case data
        $case = $triggerData->getEntityData('Case');
        if (!empty($case['contact_id'])) {
          if (is_array($case['contact_id'])) {
            $contactId = !empty($case['contact_id']) ? reset($case['contact_id']) : NULL;
          } else {
            $contactId = $case['contact_id'];
          }
        }
      }

      // If no case_id in activity, try to get it from URL parameter
      if (empty($caseId)) {
        $caseId = CRM_Utils_Request::retrieve('caseid', 'Positive');
      }

      // If still no case_id, try to get it from the trigger data
      if (empty($caseId)) {
        $case = $triggerData->getEntityData('Case');
        if (!empty($case['id'])) {
          $caseId = $case['id'];
        }
      }

      // If still no case ID, try to get it from the contact's active cases
      if (empty($caseId)) {
        $contactId = $triggerData->getContactId();
        if ($contactId) {
          $cases = civicrm_api3('Case', 'get', [
            'contact_id' => $contactId,
            'is_deleted' => 0,
            'status_id' => ['!=' => 'Closed'],
            'options' => ['limit' => 1, 'sort' => 'id DESC']
          ]);
          
          if ($cases['count'] > 0) {
            $caseId = $cases['values'][0]['id'];
          }
        }
      }

      if (empty($caseId)) {
        $this->logAction('No case ID found for activity', $triggerData, \Psr\Log\LogLevel::ERROR);
        return;
      }

      if (empty($contactId)) {
        $this->logAction('No contact ID found', $triggerData, \Psr\Log\LogLevel::ERROR);
        return;
      }

      // Determine entry type and program based on activity type
      $entryInfo = $this->getEntryInfo($activityType);
      if (!$entryInfo) {
        $this->logAction('Activity type not supported for ledger posting: ' . $activityType, $triggerData, \Psr\Log\LogLevel::WARNING);
        return;
      }

      // Calculate units based on entry type
      $units = $this->calculateUnits($activity, $entryInfo);
      if ($units === NULL) {
        $this->logAction('Could not calculate units for activity', $triggerData, \Psr\Log\LogLevel::WARNING);
        return;
      }

      // Get the operation (create/update/delete) from trigger data
      $operation = $this->getOperation($triggerData);
      
      // Handle the posting based on operation
      $this->handlePosting($activityId, $caseId, $contactId, $entryInfo, $units, $operation, $triggerData);

    } catch (Exception $e) {
      $this->logAction('Error in PostDelta: ' . $e->getMessage(), $triggerData, \Psr\Log\LogLevel::ERROR);
    }
  }

  /**
   * Returns a redirect url to extra data input from the user after adding an action
   *
   * @param int $ruleActionId
   * @return bool|string
   */
  public function getExtraDataInputUrl($ruleActionId) {
    return FALSE; // No extra configuration needed
  }

  /**
   * Returns a user friendly text explaining the action params
   *
   * @return string
   */
  public function userFriendlyConditionParams() {
    return 'Post delta to Unit Ledger';
  }

  /**
   * Get help text for the action
   *
   * @param string $context
   * @return string
   */
  public function getHelpText(string $context): string {
    switch ($context) {
      case 'actionDescription':
        return E::ts('This action posts a delta to the Unit Ledger system.');
      
      case 'actionDescriptionWithParams':
        return $this->userFriendlyConditionParams();
      
      case 'actionParamsHelp':
      default:
        return E::ts('This action will post a delta to the Unit Ledger when the rule conditions are met.');
    }
  }

  /**
   * Get entry information based on activity type
   *
   * @param int $activityTypeId
   * @return array|null
   */
  private function getEntryInfo($activityTypeId) {
    try {
      // Ensure we have a valid activity type ID
      if (empty($activityTypeId) || !is_numeric($activityTypeId)) {
        return NULL;
      }
      
      // Get activity type name
      $result = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_type',
        'value' => (int) $activityTypeId,
        'return' => 'name'
      ]);
      
      // Debug: Log the full result
      $this->logAction("API Result: " . json_encode($result), NULL, \Psr\Log\LogLevel::INFO);
      
      if ($result['count'] == 0) {
        $this->logAction("No activity type found for ID: " . $activityTypeId, NULL, \Psr\Log\LogLevel::WARNING);
        return NULL;
      }
      
      $activityType = $result['values'][$result['id']]['name'];
      $this->logAction("Found activity type: " . $activityType . " (ID: " . $activityTypeId . ")", NULL, \Psr\Log\LogLevel::INFO);

      $entryMap = [
        'FCS Housing Authorization' => ['entry_type' => 'deposit', 'program' => 'Housing'],
        'FCS Housing Authorization (Allocation)' => ['entry_type' => 'deposit', 'program' => 'Housing'],
        'FCS Employment Authorization' => ['entry_type' => 'deposit', 'program' => 'Employment'],
        'FCS Employment Authorization (Allocation)' => ['entry_type' => 'deposit', 'program' => 'Employment'],
        'Housing Units Delivered' => ['entry_type' => 'delivery', 'program' => 'Housing'],
        'Employment Units Delivered' => ['entry_type' => 'delivery', 'program' => 'Employment'],
        'Unit Allocation - Housing' => ['entry_type' => 'adjustment', 'program' => 'Housing'],
        'Unit Allocation - Employment' => ['entry_type' => 'adjustment', 'program' => 'Employment'],
      ];

      return $entryMap[$activityType] ?? NULL;
      
    } catch (Exception $e) {
      $this->logAction("Error in getEntryInfo: " . $e->getMessage(), NULL, \Psr\Log\LogLevel::ERROR);
      return NULL;
    }
  }

  /**
   * Calculate units based on activity and entry info
   *
   * @param array $activity
   * @param array $entryInfo
   * @return int|null
   */
  private function calculateUnits($activity, $entryInfo) {
    if ($entryInfo['entry_type'] === 'deposit') {
      // For authorizations, get units from Housing/Employment Units Allocated fields
      $fieldName = $this->getCustomFieldName($entryInfo['program'] . ' Units Allocated');
      if ($fieldName) {
        return $activity[$fieldName] ?? 0;
      }
      return 0;
    }
    elseif ($entryInfo['entry_type'] === 'delivery') {
      // For deliveries, convert duration to units
      $duration = $activity['duration'] ?? 0;
      $multiplier = ($entryInfo['program'] === 'Housing') ? 1 : 4;
      return $duration * $multiplier;
    }
    elseif ($entryInfo['entry_type'] === 'adjustment') {
      // For adjustments, get units from Housing/Employment Units Allocated fields
      $fieldName = $this->getCustomFieldName($entryInfo['program'] . ' Units Allocated');
      if ($fieldName) {
        return $activity[$fieldName] ?? 0;
      }
      return 0;
    }

    return NULL;
  }

  /**
   * Get custom field name by label
   *
   * @param string $label
   * @return string|null
   */
  private function getCustomFieldName($label) {
    static $fieldCache = [];
    
    if (isset($fieldCache[$label])) {
      return $fieldCache[$label];
    }
    
    try {
      $sql = "
        SELECT cf.column_name 
        FROM civicrm_custom_field cf
        JOIN civicrm_custom_group cg ON cf.custom_group_id = cg.id
        WHERE cf.label = %1 AND cg.is_active = 1
      ";
      
      $params = [1 => [$label, 'String']];
      $dao = CRM_Core_DAO::executeQuery($sql, $params);
      
      if ($dao->fetch()) {
        $fieldCache[$label] = $dao->column_name;
        return $dao->column_name;
      }
    } catch (Exception $e) {
      $this->logAction("Error finding custom field '{$label}': " . $e->getMessage(), NULL, \Psr\Log\LogLevel::ERROR);
    }
    
    return NULL;
  }

  /**
   * Get the operation from trigger data
   *
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   * @return string
   */
  private function getOperation($triggerData) {
    // This is a simplified approach - in reality you'd need to track this better
    // For now, we'll assume it's a create operation
    return 'create';
  }

  /**
   * Handle the actual posting to ledger
   *
   * @param int $activityId
   * @param int $caseId
   * @param int $contactId
   * @param array $entryInfo
   * @param int $units
   * @param string $operation
   * @param CRM_Civirules_TriggerData_TriggerData $triggerData
   */
  private function handlePosting($activityId, $caseId, $contactId, $entryInfo, $units, $operation, $triggerData) {
    // For now, we'll create a simple ledger entry
    // In a full implementation, this would use a proper ledger table
    
    $ledgerData = [
      'activity_id' => $activityId,
      'case_id' => $caseId,
      'contact_id' => $contactId,
      'program' => $entryInfo['program'],
      'entry_type' => $entryInfo['entry_type'],
      'units_delta' => $units,
      'operation' => $operation,
      'created_date' => date('Y-m-d H:i:s'),
      'created_by' => CRM_Core_Session::getLoggedInContactID(),
    ];

    // Store in a simple custom table for now
    $this->insertLedgerEntry($ledgerData);

    $this->logAction("Posted {$entryInfo['entry_type']} of {$units} units for {$entryInfo['program']} program", $triggerData);
  }

  /**
   * Insert ledger entry
   *
   * @param array $data
   */
  private function insertLedgerEntry($data) {
    // Calculate running balance
    $balanceAfter = $this->calculateRunningBalance($data['case_id'], $data['program'], $data['units_delta']);
    $this->logAction("insertLedgerEntry: $balanceAfter");
    // Add balance to data
    $data['balance_after'] = $balanceAfter;
    
    // Insert into ledger table
    $sql = "
      INSERT INTO civicrm_unit_ledger 
      (activity_id, case_id, contact_id, program, entry_type, units_delta, balance_after, operation, description, created_date, created_by)
      VALUES (%1, %2, %3, %4, %5, %6, %7, %8, %9, %10, %11)
    ";
    
    $params = [
      1 => [$data['activity_id'], 'Integer'],
      2 => [$data['case_id'], 'Integer'],
      3 => [$data['contact_id'], 'Integer'],
      4 => [$data['program'], 'String'],
      5 => [$data['entry_type'], 'String'],
      6 => [$data['units_delta'], 'Integer'],
      7 => [$data['balance_after'], 'Integer'],
      8 => [$data['operation'], 'String'],
      9 => [$data['description'] ?? '', 'String'],
      10 => [$data['created_date'], 'String'],
      11 => [$data['created_by'], 'Integer'],
    ];
    
    CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Calculate running balance for a case/program
   *
   * @param int $caseId
   * @param string $program
   * @param int $newDelta
   * @return int
   */
  private function calculateRunningBalance($caseId, $program, $newDelta) {
    // Get current balance
    $sql = "
      SELECT COALESCE(SUM(units_delta), 0) as current_balance
      FROM civicrm_unit_ledger 
      WHERE case_id = %1 AND program = %2
    ";
    
    $params = [
      1 => [$caseId, 'Integer'],
      2 => [$program, 'String'],
    ];
    
    $result = CRM_Core_DAO::executeQuery($sql, $params);
    $currentBalance = 0;
    
    if ($result->fetch()) {
      $currentBalance = (int) $result->current_balance;
    }
    
    return $currentBalance + $newDelta;
  }
}
