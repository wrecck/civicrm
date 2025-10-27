<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Upgrader for UnitLedger extension
 */
class CRM_UnitLedger_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Perform actions after install
   */
  public function postInstall() {
    $this->createLedgerTable();
    $this->addUnitLedgerAction();
    $this->addUnitLedgerMenu();
  }

  /**
   * Add the Unit Ledger action to CiviRules
   */
  private function addUnitLedgerAction() {
    // Check if CiviRules is installed
    if (!CRM_Core_DAO::checkTableExists('civirule_action')) {
      throw new Exception('CiviRules extension must be installed before installing UnitLedger extension.');
    }

    // Insert the action
    $sql = "INSERT INTO civirule_action (name, label, class_name, is_active) 
            VALUES ('unitledger_post_delta', 'Post Delta to Unit Ledger', 'CRM_UnitLedger_CiviRules_Action_PostDelta', 1)";
    
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Create the Unit Ledger table
   */
  private function createLedgerTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS `civicrm_unit_ledger` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `activity_id` int(10) unsigned DEFAULT NULL COMMENT 'Source activity ID',
        `case_id` int(10) unsigned NOT NULL COMMENT 'Case ID',
        `contact_id` int(10) unsigned NOT NULL COMMENT 'Contact ID',
        `program` varchar(50) NOT NULL COMMENT 'Program type (Housing/Employment)',
        `entry_type` varchar(50) NOT NULL COMMENT 'Entry type (deposit/delivery/adjustment)',
        `units_delta` int(11) NOT NULL COMMENT 'Units change (+/-)',
        `balance_after` int(11) NOT NULL COMMENT 'Running balance after this entry',
        `operation` varchar(20) NOT NULL COMMENT 'Operation (create/update/delete)',
        `description` text COMMENT 'Entry description',
        `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `created_by` int(10) unsigned DEFAULT NULL COMMENT 'User who created this entry',
        PRIMARY KEY (`id`),
        KEY `idx_case_id` (`case_id`),
        KEY `idx_activity_id` (`activity_id`),
        KEY `idx_contact_id` (`contact_id`),
        KEY `idx_program` (`program`),
        KEY `idx_entry_type` (`entry_type`),
        KEY `idx_created_date` (`created_date`),
        CONSTRAINT `FK_civicrm_unit_ledger_activity_id` FOREIGN KEY (`activity_id`) REFERENCES `civicrm_activity` (`id`) ON DELETE SET NULL,
        CONSTRAINT `FK_civicrm_unit_ledger_case_id` FOREIGN KEY (`case_id`) REFERENCES `civicrm_case` (`id`) ON DELETE CASCADE,
        CONSTRAINT `FK_civicrm_unit_ledger_contact_id` FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact` (`id`) ON DELETE CASCADE,
        CONSTRAINT `FK_civicrm_unit_ledger_created_by` FOREIGN KEY (`created_by`) REFERENCES `civicrm_contact` (`id`) ON DELETE SET NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Unit Transaction Ledger - append-only ledger for tracking unit allocations and deliveries'
    ";
    
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Add Unit Ledger menu item under Reports
   */
  private function addUnitLedgerMenu() {
    // First, delete any existing entry
    CRM_Core_DAO::executeQuery("DELETE FROM `civicrm_navigation` WHERE name = 'unit_ledger' and parent_id IS NULL");
    
    // Get the Reports menu ID
    $reportsNavId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Reports', 'id', 'name');
    
    if (!$reportsNavId) {
      // If Reports doesn't exist, try to find it by label
      $reportsNavId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Reports', 'id', 'label');
    }
    
    if (!$reportsNavId) {
      CRM_Core_Error::debug_log_message('UnitLedger: Could not find Reports menu item');
      return;
    }
    
    // Create navigation entry
    $navigation = new CRM_Core_DAO_Navigation();
    $params = array(
      'domain_id'  => CRM_Core_Config::domainID(),
      'label'      => E::ts('Unit Ledger'),
      'name'       => 'unit_ledger',
      'url'        => 'civicrm/unitledger',
      'parent_id'  => $reportsNavId,
      'weight'     => 0,
      'permission' => 'access CiviCRM',
      'separator'  => 1,
      'is_active'  => 1
    );
    $navigation->copyValues($params);
    $navigation->save();
    
    // Reset navigation cache
    CRM_Core_BAO_Navigation::resetNavigation();
    
    CRM_Core_Error::debug_log_message('UnitLedger: Added Unit Ledger menu item under Reports');
  }

  /**
   * Remove the action, table, and menu on uninstall
   */
  public function uninstall() {
    $sql = "DELETE FROM civirule_action WHERE name = 'unitledger_post_delta'";
    CRM_Core_DAO::executeQuery($sql);
    
    $sql = "DELETE FROM civicrm_navigation WHERE name = 'unit_ledger'";
    CRM_Core_DAO::executeQuery($sql);
    
    $sql = "DROP TABLE IF EXISTS civicrm_unit_ledger";
    CRM_Core_DAO::executeQuery($sql);
    
    // Reset navigation cache
    CRM_Core_BAO_Navigation::resetNavigation();
  }
}
