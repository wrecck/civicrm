<?php

use CRM_UnitLedger_ExtensionUtil as E;

/**
 * Upgrader class for Unit Ledger extension
 */
class CRM_UnitLedger_Upgrader extends CRM_Extension_Upgrader_Base {

  /**
   * Perform actions after install
   */
  public function postInstall() {
    $this->registerCiviRulesActions();
  }

  /**
   * Perform actions after enable
   */
  public function postEnable() {
    $this->registerCiviRulesActions();
  }

  /**
   * Example of a versioned upgrade step (optional)
   * Called when upgrading from version 1.0.0 to 1.1.0
   */
  public function upgrade_1100() {
    $this->registerCiviRulesActions();
    return TRUE;
  }

  /**
   * Register CiviRules actions from JSON file
   */
  private function registerCiviRulesActions() {
    if (!method_exists('CRM_Civirules_Utils_Upgrader', 'insertActionsFromJson')) {
      CRM_Core_Error::debug_log_message(
        'CiviRules not enabled; skipping Unit Ledger action registration.'
      );
      return;
    }

    $jsonFile = E::path('civirules_actions.json');
    if (file_exists($jsonFile)) {
      CRM_Civirules_Utils_Upgrader::insertActionsFromJson($jsonFile);
    } else {
      CRM_Core_Error::debug_log_message(
        "Unit Ledger Upgrader could not find civirules_actions.json at {$jsonFile}"
      );
    }
  }
}
