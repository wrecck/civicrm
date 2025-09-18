<?php

namespace CRM\UnitLedger\BAO;

use CRM_UnitLedger_ExtensionUtil as E;
use CRM_Core_DAO;

/**
 * UnitLedger BAO Class
 * 
 * Handles business logic for unit ledger operations.
 */
class UnitLedger extends CRM_Core_DAO {

  /**
   * Table name
   */
  public static $_tableName = 'civicrm_unit_ledger';

  /**
   * Primary key
   */
  public static $_primaryKey = 'id';

  /**
   * Fields
   */
  public static $_fields = [
    'id' => [
      'name' => 'id',
      'type' => CRM_Utils_Type::T_INT,
      'title' => E::ts('ID'),
      'description' => E::ts('Unique UnitLedger ID'),
      'required' => TRUE,
      'import' => FALSE,
      'where' => 'civicrm_unit_ledger.id',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => FALSE,
    ],
    'activity_id' => [
      'name' => 'activity_id',
      'type' => CRM_Utils_Type::T_INT,
      'title' => E::ts('Activity ID'),
      'description' => E::ts('FK to Activity'),
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.activity_id',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
      'FKClassName' => 'CRM_Activity_DAO_Activity',
    ],
    'case_id' => [
      'name' => 'case_id',
      'type' => CRM_Utils_Type::T_INT,
      'title' => E::ts('Case ID'),
      'description' => E::ts('FK to Case'),
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.case_id',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
      'FKClassName' => 'CRM_Case_DAO_Case',
    ],
    'contact_id' => [
      'name' => 'contact_id',
      'type' => CRM_Utils_Type::T_INT,
      'title' => E::ts('Contact ID'),
      'description' => E::ts('FK to Contact'),
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.contact_id',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
      'FKClassName' => 'CRM_Contact_DAO_Contact',
    ],
    'program' => [
      'name' => 'program',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => E::ts('Program'),
      'description' => E::ts('Program name (e.g., FCS, WellPoint)'),
      'maxlength' => 255,
      'size' => CRM_Utils_Type::HUGE,
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.program',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'units' => [
      'name' => 'units',
      'type' => CRM_Utils_Type::T_MONEY,
      'title' => E::ts('Units'),
      'description' => E::ts('Number of units (positive for credits, negative for debits)'),
      'required' => TRUE,
      'precision' => [10, 2],
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.units',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'unit_type' => [
      'name' => 'unit_type',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => E::ts('Unit Type'),
      'description' => E::ts('Type of units (hours, days, sessions, etc.)'),
      'maxlength' => 50,
      'size' => CRM_Utils_Type::MEDIUM,
      'default' => 'hours',
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.unit_type',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'transaction_date' => [
      'name' => 'transaction_date',
      'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
      'title' => E::ts('Transaction Date'),
      'description' => E::ts('Date/time of the transaction'),
      'required' => TRUE,
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.transaction_date',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'balance' => [
      'name' => 'balance',
      'type' => CRM_Utils_Type::T_MONEY,
      'title' => E::ts('Balance'),
      'description' => E::ts('Running balance after this transaction'),
      'required' => TRUE,
      'precision' => [10, 2],
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.balance',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'last_posted_units' => [
      'name' => 'last_posted_units',
      'type' => CRM_Utils_Type::T_MONEY,
      'title' => E::ts('Last Posted Units'),
      'description' => E::ts('Last units posted for this activity (for idempotency)'),
      'precision' => [10, 2],
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.last_posted_units',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'transaction_type' => [
      'name' => 'transaction_type',
      'type' => CRM_Utils_Type::T_STRING,
      'title' => E::ts('Transaction Type'),
      'description' => E::ts('Type of transaction (activity, adjustment, import)'),
      'maxlength' => 50,
      'size' => CRM_Utils_Type::MEDIUM,
      'default' => 'activity',
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.transaction_type',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'description' => [
      'name' => 'description',
      'type' => CRM_Utils_Type::T_TEXT,
      'title' => E::ts('Description'),
      'description' => E::ts('Description of the transaction'),
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.description',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'created_date' => [
      'name' => 'created_date',
      'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
      'title' => E::ts('Created Date'),
      'description' => E::ts('When this record was created'),
      'required' => TRUE,
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.created_date',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
    ],
    'created_by' => [
      'name' => 'created_by',
      'type' => CRM_Utils_Type::T_INT,
      'title' => E::ts('Created By'),
      'description' => E::ts('FK to Contact who created this record'),
      'import' => TRUE,
      'where' => 'civicrm_unit_ledger.created_by',
      'headerPattern' => '',
      'dataPattern' => '',
      'export' => TRUE,
      'FKClassName' => 'CRM_Contact_DAO_Contact',
    ],
  ];

  /**
   * Constructor
   */
  public function __construct() {
    $this->__table = 'civicrm_unit_ledger';
    parent::__construct();
  }

  /**
   * Get query for APIv4
   */
  public static function getQuery($action) {
    $query = new \Civi\Api4\Query\Api4SelectQuery('UnitLedger', $action->getCheckPermissions());
    $query->where = $action->getWhere();
    $query->orderBy = $action->getOrderBy();
    $query->limit = $action->getLimit();
    $query->offset = $action->getOffset();
    $query->select = $action->getSelect();
    $query->groupBy = $action->getGroupBy();
    $query->having = $action->getHaving();
    $query->join = $action->getJoin();
    
    return $query->run();
  }

  /**
   * Create record for APIv4
   */
  public static function createRecord($action) {
    $values = $action->getValues();
    
    // Set defaults
    if (empty($values['transaction_date'])) {
      $values['transaction_date'] = date('Y-m-d H:i:s');
    }
    if (empty($values['created_date'])) {
      $values['created_date'] = date('Y-m-d H:i:s');
    }
    if (empty($values['created_by'])) {
      $values['created_by'] = CRM_Core_Session::getLoggedInContactID();
    }
    
    // Calculate balance if not provided
    if (!isset($values['balance'])) {
      $values['balance'] = self::calculateBalance($values);
    }
    
    $dao = new self();
    $dao->copyValues($values);
    $dao->save();
    
    return $dao->toArray();
  }

  /**
   * Update record for APIv4
   */
  public static function updateRecord($action) {
    $values = $action->getValues();
    $where = $action->getWhere();
    
    $dao = new self();
    $dao->copyValues($values);
    
    // Apply where conditions
    foreach ($where as $condition) {
      $dao->addWhere($condition[0], $condition[1], $condition[2]);
    }
    
    $dao->update();
    
    return $dao->toArray();
  }

  /**
   * Delete record for APIv4
   */
  public static function deleteRecord($action) {
    $where = $action->getWhere();
    
    $dao = new self();
    
    // Apply where conditions
    foreach ($where as $condition) {
      $dao->addWhere($condition[0], $condition[1], $condition[2]);
    }
    
    return $dao->delete();
  }

  /**
   * Get fields for APIv4
   */
  public static function getFields($action) {
    return self::$_fields;
  }

  /**
   * Calculate balance for a new transaction
   */
  public static function calculateBalance($values) {
    $caseId = $values['case_id'] ?? NULL;
    $program = $values['program'] ?? NULL;
    $transactionDate = $values['transaction_date'] ?? date('Y-m-d H:i:s');
    
    if (!$caseId || !$program) {
      return $values['units'] ?? 0;
    }
    
    // Get the last balance for this case/program before this transaction
    $dao = new self();
    $dao->case_id = $caseId;
    $dao->program = $program;
    $dao->whereAdd("transaction_date < '" . CRM_Utils_Type::escape($transactionDate, 'String') . "'");
    $dao->orderBy('transaction_date DESC, id DESC');
    $dao->limit(1);
    $dao->find(TRUE);
    
    $lastBalance = $dao->balance ?? 0;
    $newUnits = $values['units'] ?? 0;
    
    return $lastBalance + $newUnits;
  }

  /**
   * Get balance for a specific case/program/date combination
   */
  public static function getCaseBalance($caseId, $program = NULL, $asOfDate = NULL) {
    if (!$caseId) {
      return 0;
    }
    
    $asOfDate = $asOfDate ?: date('Y-m-d H:i:s');
    
    $dao = new self();
    $dao->case_id = $caseId;
    if ($program) {
      $dao->program = $program;
    }
    $dao->whereAdd("transaction_date <= '" . CRM_Utils_Type::escape($asOfDate, 'String') . "'");
    $dao->orderBy('transaction_date DESC, id DESC');
    $dao->limit(1);
    $dao->find(TRUE);
    
    return $dao->balance ?? 0;
  }

  /**
   * Get all balances for a case grouped by program
   */
  public static function getCaseBalancesByProgram($caseId, $asOfDate = NULL) {
    if (!$caseId) {
      return [];
    }
    
    $asOfDate = $asOfDate ?: date('Y-m-d H:i:s');
    
    $dao = new self();
    $dao->case_id = $caseId;
    $dao->whereAdd("transaction_date <= '" . CRM_Utils_Type::escape($asOfDate, 'String') . "'");
    $dao->orderBy('program ASC, transaction_date DESC, id DESC');
    $dao->find();
    
    $balances = [];
    $processedPrograms = [];
    
    while ($dao->fetch()) {
      if (!in_array($dao->program, $processedPrograms)) {
        $balances[$dao->program] = $dao->balance;
        $processedPrograms[] = $dao->program;
      }
    }
    
    return $balances;
  }

  /**
   * Recompute balances for a case and date range
   */
  public static function recomputeBalances($action) {
    $params = $action->getParams();
    $caseId = $params['case_id'] ?? NULL;
    $program = $params['program'] ?? NULL;
    $startDate = $params['start_date'] ?? NULL;
    $endDate = $params['end_date'] ?? NULL;
    
    if (!$caseId) {
      throw new \CRM_Core_Exception('Case ID is required for balance recomputation');
    }
    
    // Get all transactions for this case/program/date range
    $dao = new self();
    $dao->case_id = $caseId;
    if ($program) {
      $dao->program = $program;
    }
    if ($startDate) {
      $dao->whereAdd("transaction_date >= '" . CRM_Utils_Type::escape($startDate, 'String') . "'");
    }
    if ($endDate) {
      $dao->whereAdd("transaction_date <= '" . CRM_Utils_Type::escape($endDate, 'String') . "'");
    }
    $dao->orderBy('transaction_date ASC, id ASC');
    $dao->find();
    
    $runningBalance = 0;
    $updated = 0;
    
    while ($dao->fetch()) {
      $runningBalance += $dao->units;
      
      if ($dao->balance != $runningBalance) {
        $updateDao = new self();
        $updateDao->id = $dao->id;
        $updateDao->balance = $runningBalance;
        $updateDao->update();
        $updated++;
      }
    }
    
    return [
      'updated_records' => $updated,
      'case_id' => $caseId,
      'program' => $program,
      'start_date' => $startDate,
      'end_date' => $endDate,
    ];
  }

  /**
   * Post units for an activity (delta-aware)
   */
  public static function postDelta($action) {
    $params = $action->getParams();
    $activityId = $params['activity_id'] ?? NULL;
    
    if (!$activityId) {
      throw new \CRM_Core_Exception('Activity ID is required for posting units');
    }
    
    // Get activity details
    $activity = civicrm_api3('Activity', 'getsingle', [
      'id' => $activityId,
      'return' => ['activity_type_id', 'case_id', 'target_contact_id', 'activity_date_time'],
    ]);
    
    // Get program mapping for this activity type
    $programMappings = Civi::settings()->get('unitledger_program_mappings');
    $programMappings = json_decode($programMappings, TRUE) ?: [];
    
    $activityTypeId = $activity['activity_type_id'];
    $program = $programMappings[$activityTypeId] ?? NULL;
    
    if (!$program) {
      return ['status' => 'skipped', 'reason' => 'No program mapping for activity type'];
    }
    
    // Get unit multiplier for this program
    $unitMultipliers = Civi::settings()->get('unitledger_unit_multipliers');
    $unitMultipliers = json_decode($unitMultipliers, TRUE) ?: [];
    $multiplier = $unitMultipliers[$program] ?? 1;
    
    // Calculate units (assuming 1 unit per activity, multiplied by program multiplier)
    $units = 1 * $multiplier;
    
    // Get last posted units for idempotency
    $lastPostedUnits = self::getLastPostedUnits($activityId);
    
    // Calculate delta
    $delta = $units - $lastPostedUnits;
    
    if ($delta == 0) {
      return ['status' => 'no_change', 'units' => $units, 'delta' => $delta];
    }
    
    // Create ledger entry
    $ledgerEntry = [
      'activity_id' => $activityId,
      'case_id' => $activity['case_id'] ?? NULL,
      'contact_id' => $activity['target_contact_id'] ?? NULL,
      'program' => $program,
      'units' => $delta,
      'unit_type' => 'hours',
      'transaction_date' => $activity['activity_date_time'],
      'transaction_type' => 'activity',
      'description' => "Activity {$activityId} - {$program}",
      'last_posted_units' => $units,
    ];
    
    $result = civicrm_api4('UnitLedger', 'create', ['values' => $ledgerEntry]);
    
    // Update activity with last posted units
    self::updateLastPostedUnits($activityId, $units);
    
    return [
      'status' => 'posted',
      'units' => $units,
      'delta' => $delta,
      'ledger_id' => $result->first()['id'],
    ];
  }

  /**
   * Get balance for a case/program/date
   */
  public static function getBalance($action) {
    $params = $action->getParams();
    $caseId = $params['case_id'] ?? NULL;
    $program = $params['program'] ?? NULL;
    $asOfDate = $params['as_of_date'] ?? date('Y-m-d H:i:s');
    
    if (!$caseId) {
      throw new \CRM_Core_Exception('Case ID is required for balance calculation');
    }
    
    $dao = new self();
    $dao->case_id = $caseId;
    if ($program) {
      $dao->program = $program;
    }
    $dao->whereAdd("transaction_date <= '" . CRM_Utils_Type::escape($asOfDate, 'String') . "'");
    $dao->orderBy('transaction_date DESC, id DESC');
    $dao->limit(1);
    $dao->find(TRUE);
    
    return [
      'balance' => $dao->balance ?? 0,
      'case_id' => $caseId,
      'program' => $program,
      'as_of_date' => $asOfDate,
    ];
  }

  /**
   * Get last posted units for an activity
   */
  private static function getLastPostedUnits($activityId) {
    $customFieldId = self::getLastPostedUnitsFieldId();
    if (!$customFieldId) {
      return 0;
    }
    
    $result = civicrm_api3('Activity', 'getvalue', [
      'id' => $activityId,
      "custom_{$customFieldId}" => TRUE,
      'return' => "custom_{$customFieldId}",
    ]);
    
    return (float) $result;
  }

  /**
   * Update last posted units for an activity
   */
  private static function updateLastPostedUnits($activityId, $units) {
    $customFieldId = self::getLastPostedUnitsFieldId();
    if (!$customFieldId) {
      return;
    }
    
    civicrm_api3('Activity', 'create', [
      'id' => $activityId,
      "custom_{$customFieldId}" => $units,
    ]);
  }

  /**
   * Get the custom field ID for last posted units
   */
  private static function getLastPostedUnitsFieldId() {
    static $fieldId = NULL;
    
    if ($fieldId === NULL) {
      $result = civicrm_api3('CustomField', 'get', [
        'custom_group_id' => 'unit_ledger_tracking',
        'name' => 'last_posted_units',
        'return' => ['id'],
      ]);
      
      $fieldId = $result['values'][0]['id'] ?? FALSE;
    }
    
    return $fieldId;
  }

}
