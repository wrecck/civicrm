<?php

/**
 * @file
 * UnitLedger DAO Class
 */

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * UnitLedger DAO Class
 * 
 * This class handles database operations for the unit ledger.
 */
class CRM_UnitLedger_DAO_UnitLedger extends CRM_Core_DAO {

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
  public static $_fields = NULL;

  /**
   * Field definitions
   */
  public static function fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
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
    }
    return self::$_fields;
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'unit_ledger', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'unit_ledger', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   */
  public static function indices($localize = TRUE) {
    $indices = [
      'idx_activity' => [
        'name' => 'idx_activity',
        'field' => ['activity_id'],
        'localizable' => FALSE,
        'sig' => 'civicrm_unit_ledger::0::activity_id',
      ],
      'idx_case' => [
        'name' => 'idx_case',
        'field' => ['case_id'],
        'localizable' => FALSE,
        'sig' => 'civicrm_unit_ledger::0::case_id',
      ],
      'idx_contact' => [
        'name' => 'idx_contact',
        'field' => ['contact_id'],
        'localizable' => FALSE,
        'sig' => 'civicrm_unit_ledger::0::contact_id',
      ],
      'idx_program_date' => [
        'name' => 'idx_program_date',
        'field' => ['program', 'transaction_date'],
        'localizable' => FALSE,
        'sig' => 'civicrm_unit_ledger::0::program::transaction_date',
      ],
    ];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
