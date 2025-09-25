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
    $this->addUnitLedgerAction();
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
   * Remove the action on uninstall
   */
  public function uninstall() {
    $sql = "DELETE FROM civirule_action WHERE name = 'unitledger_post_delta'";
    CRM_Core_DAO::executeQuery($sql);
  }
}
